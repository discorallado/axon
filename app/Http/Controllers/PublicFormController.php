<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicFormSubmitRequest;
use App\Models\Attachment;
use App\Models\FormTemplate;
use App\Models\SubmissionAnswer;
use App\Models\SubmissionRequest;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use App\Notifications\NewSubmissionReceived;
use App\Notifications\SubmissionConfirmed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicFormController extends Controller
{
    public function show(string $slug): View
    {
        $template = FormTemplate::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'currentSections.questions' => fn ($q) => $q->where('template_version', DB::raw('form_sections.template_version')),
                'currentConditionalRules',
            ])
            ->firstOrFail();

        $sections = $template->currentSections()
            ->with(['questions' => fn ($q) => $q->where('template_version', $template->current_version)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $rules = $template->currentConditionalRules()
            ->with(['triggerQuestion', 'targetQuestion', 'targetSection'])
            ->get()
            ->map(fn ($rule) => [
                'trigger_question_id' => $rule->trigger_question_id,
                'operator' => $rule->operator->value,
                'trigger_value' => $rule->trigger_value,
                'action' => $rule->action,
                'target_type' => $rule->target_type,
                'target_id' => $rule->target_type === 'question'
                    ? $rule->target_question_id
                    : $rule->target_section_id,
            ]);

        return view('public.forms.show', [
            'template' => $template,
            'sections' => $sections,
            'rules' => $rules,
            'ts' => time(),
        ]);
    }

    public function submit(PublicFormSubmitRequest $request, string $slug): RedirectResponse
    {
        $template = FormTemplate::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if ($request->isSuspectedSpam()) {
            return back()
                ->withInput()
                ->withErrors(['_spam' => __('forms.public.spam_error')]);
        }

        $questions = $template->currentQuestions()->get()->keyBy('id');

        // Determinar qué preguntas son visibles según reglas condicionales
        $visibleQuestionIds = $this->resolveVisibleQuestions($template, $request->input('answers', []));

        // Validar preguntas required que están visibles
        $dynamicRules = [];
        $dynamicMessages = [];
        $dynamicAttributes = [];

        foreach ($questions->filter(fn ($q) => in_array($q->id, $visibleQuestionIds)) as $question) {
            if (! $question->is_required) {
                continue;
            }

            if ($question->type->isFile()) {
                $dynamicRules["files.{$question->id}"] = ['required'];
                $dynamicMessages["files.{$question->id}.required"] = "El campo \"{$question->label}\" es obligatorio.";
            } else {
                $dynamicRules["answers.{$question->id}"] = ['required'];
                $dynamicMessages["answers.{$question->id}.required"] = "El campo \"{$question->label}\" es obligatorio.";
            }

            $dynamicAttributes["answers.{$question->id}"] = $question->label;
        }

        if ($dynamicRules) {
            $validator = Validator::make($request->all(), $dynamicRules, $dynamicMessages, $dynamicAttributes);
            if ($validator->fails()) {
                return back()->withInput()->withErrors($validator);
            }
        }

        $submission = DB::transaction(function () use ($request, $template, $questions, $visibleQuestionIds) {
            $initialStatus = $template->organization->submissionStatuses()
                ->where('is_initial', true)
                ->first();

            abort_unless($initialStatus, 500, 'No hay estado inicial configurado.');

            $submission = SubmissionRequest::create([
                'organization_id' => $template->organization_id,
                'form_template_id' => $template->id,
                'template_version' => $template->current_version,
                'reference_code' => $this->generateReferenceCode($template->organization_id),
                'status_id' => $initialStatus->id,
                'submitter_name' => $request->submitter_name,
                'submitter_email' => $request->submitter_email,
                'submitter_phone' => $request->submitter_phone,
                'submitter_company' => $request->submitter_company,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit($request->userAgent() ?? '', 290),
                'submitted_at' => now(),
            ]);

            foreach ($questions as $question) {
                if (! in_array($question->id, $visibleQuestionIds)) {
                    continue;
                }

                $rawValue = $request->input("answers.{$question->id}");

                $answer = SubmissionAnswer::create([
                    'organization_id' => $template->organization_id,
                    'submission_request_id' => $submission->id,
                    'form_question_id' => $question->id,
                    'question_key' => $question->key,
                    'question_label' => $question->label,
                    'value' => is_array($rawValue) ? null : $rawValue,
                    'value_json' => is_array($rawValue) ? $rawValue : null,
                ]);

                // Procesar archivos adjuntos (múltiples) si la pregunta es de tipo file
                if ($question->type->isFile() && $request->hasFile("files.{$question->id}")) {
                    $files = (array) $request->file("files.{$question->id}");

                    foreach ($files as $file) {
                        if (! $file || ! $file->isValid()) {
                            continue;
                        }

                        $path = $file->store("submissions/{$submission->id}", 'local');

                        Attachment::create([
                            'organization_id' => $template->organization_id,
                            'attachable_type' => SubmissionAnswer::class,
                            'attachable_id' => $answer->id,
                            'disk' => 'local',
                            'path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getMimeType(),
                            'size_bytes' => $file->getSize(),
                            'uploaded_by' => null,
                        ]);
                    }
                }
            }

            // Registrar primer estado en el historial
            SubmissionStatusHistory::create([
                'organization_id' => $template->organization_id,
                'submission_request_id' => $submission->id,
                'from_status_id' => null,
                'to_status_id' => $initialStatus->id,
                'changed_by' => null,
                'comment' => 'Solicitud recibida vía formulario público.',
                'created_at' => now(),
            ]);

            return $submission;
        });

        // Notificar al equipo (in-app + email)
        $admins = User::withoutGlobalScopes()
            ->where('organization_id', $template->organization_id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'supervisor']))
            ->get();

        Notification::send($admins, new NewSubmissionReceived($submission));

        // Confirmación al solicitante
        Notification::route('mail', $submission->submitter_email)
            ->notify(new SubmissionConfirmed($submission));

        return redirect()->route('public.form.thanks', [
            'slug' => $slug,
            'reference' => $submission->reference_code,
        ]);
    }

    private function resolveVisibleQuestions(FormTemplate $template, array $answers): array
    {
        $questions = $template->currentQuestions()->get();
        $rules = $template->currentConditionalRules()->get();

        // Por defecto todas son visibles
        $visible = $questions->pluck('id')->all();

        foreach ($rules as $rule) {
            $answerValue = $answers[$rule->trigger_question_id] ?? null;
            $conditionMet = $rule->evaluate($answerValue);

            $targetId = $rule->target_type === 'question'
                ? $rule->target_question_id
                : null;

            if ($targetId === null) {
                continue;
            }

            if ($rule->action === 'show' && ! $conditionMet) {
                $visible = array_filter($visible, fn ($id) => $id !== $targetId);
            } elseif ($rule->action === 'hide' && $conditionMet) {
                $visible = array_filter($visible, fn ($id) => $id !== $targetId);
            }
        }

        return array_values($visible);
    }

    private function generateReferenceCode(string $organizationId): string
    {
        $year = now()->year;

        return DB::transaction(function () use ($organizationId, $year) {
            $count = SubmissionRequest::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->whereYear('submitted_at', $year)
                ->lockForUpdate()
                ->count();

            return 'SOL-'.$year.'-'.str_pad($count + 1, 5, '0', STR_PAD_LEFT);
        });
    }

    public function thanks(Request $request, string $slug): View
    {
        $template = FormTemplate::withoutGlobalScopes()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('public.forms.thanks', [
            'template' => $template,
            'reference' => $request->query('reference', ''),
            'email' => $request->query('email', ''),
        ]);
    }
}
