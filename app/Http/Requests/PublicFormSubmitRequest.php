<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicFormSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submitter_name' => ['required', 'string', 'max:150'],
            'submitter_email' => ['required', 'email', 'max:255'],
            'submitter_phone' => ['nullable', 'string', 'max:30'],
            'submitter_company' => ['nullable', 'string', 'max:150'],

            // Honeypot: capturamos el valor pero no validamos aquí; el controller lo evalúa silenciosamente
            '_hp' => ['nullable', 'string'],

            // Timestamp mínimo de relleno (al menos 3 segundos)
            '_ts' => ['required', 'integer'],

            // Respuestas dinámicas; se validan individualmente en el controller
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'max:20480'], // 20 MB máx por archivo
        ];
    }

    public function messages(): array
    {
        return [
            '_hp.max' => __('forms.public.spam_error'),
            'submitter_name.required' => 'El nombre completo es obligatorio.',
            'submitter_email.required' => 'El correo electrónico es obligatorio.',
            'submitter_email.email' => 'Ingrese un correo electrónico válido.',
        ];
    }

    public function isSuspectedSpam(): bool
    {
        // Campo honeypot fue rellenado
        if (filled($this->input('_hp'))) {
            return true;
        }

        // El formulario se envió en menos de 3 segundos
        $ts = (int) $this->input('_ts', 0);
        if ($ts > 0 && (time() - $ts) < 3) {
            return true;
        }

        return false;
    }
}
