<?php

use App\Enums\ConditionalOperator;
use App\Enums\FormQuestionType;
use App\Models\FormConditionalRule;
use App\Models\FormQuestion;
use App\Models\FormSection;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\SubmissionRequest;
use App\Models\SubmissionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('excludes hidden question answer when conditional hides it', function () {
    Notification::fake();

    $org = Organization::factory()->create();
    $template = FormTemplate::factory()->for($org, 'organization')->create(['is_active' => true]);

    SubmissionStatus::factory()->initial()->for($org, 'organization')->create();

    $section = FormSection::factory()->create([
        'organization_id' => $org->id,
        'form_template_id' => $template->id,
        'template_version' => 1,
    ]);

    $trigger = FormQuestion::factory()->create([
        'organization_id' => $org->id,
        'form_template_id' => $template->id,
        'form_section_id' => $section->id,
        'template_version' => 1,
        'key' => 'tipo',
        'label' => 'Tipo',
        'type' => FormQuestionType::Select,
        'options' => [
            ['value' => 'simple', 'label' => 'Simple'],
            ['value' => 'complejo', 'label' => 'Complejo'],
        ],
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $conditional = FormQuestion::factory()->create([
        'organization_id' => $org->id,
        'form_template_id' => $template->id,
        'form_section_id' => $section->id,
        'template_version' => 1,
        'key' => 'potencia_especial',
        'label' => 'Potencia especial',
        'type' => FormQuestionType::Number,
        'is_required' => false,
        'sort_order' => 2,
    ]);

    // Regla: mostrar "potencia_especial" solo si "tipo" == "complejo"
    FormConditionalRule::create([
        'organization_id' => $org->id,
        'form_template_id' => $template->id,
        'template_version' => 1,
        'trigger_question_id' => $trigger->id,
        'operator' => ConditionalOperator::Eq,
        'trigger_value' => 'complejo',
        'action' => 'show',
        'target_type' => 'question',
        'target_question_id' => $conditional->id,
        'target_section_id' => null,
    ]);

    // Enviar con tipo = "simple" → la pregunta condicional no debería guardarse
    $this->post(route('public.form.submit', $template->slug), [
        'submitter_name' => 'Ana Torres',
        'submitter_email' => 'ana@example.com',
        '_hp' => '',
        '_ts' => time() - 10,
        'answers' => [
            $trigger->id => 'simple',
            $conditional->id => '500',
        ],
    ]);

    $submission = SubmissionRequest::withoutGlobalScopes()->first();

    // Solo debe existir la respuesta del trigger (tipo), no la condicional
    expect($submission->answers()->withoutGlobalScopes()->where('question_key', 'potencia_especial')->exists())
        ->toBeFalse();
});

it('evaluates eq operator correctly', function () {
    $rule = new FormConditionalRule([
        'operator' => ConditionalOperator::Eq,
        'trigger_value' => 'complejo',
    ]);

    expect($rule->evaluate('complejo'))->toBeTrue();
    expect($rule->evaluate('simple'))->toBeFalse();
});

it('evaluates is_empty operator correctly', function () {
    $rule = new FormConditionalRule([
        'operator' => ConditionalOperator::IsEmpty,
        'trigger_value' => null,
    ]);

    expect($rule->evaluate(''))->toBeTrue();
    expect($rule->evaluate(null))->toBeTrue();
    expect($rule->evaluate('algo'))->toBeFalse();
});
