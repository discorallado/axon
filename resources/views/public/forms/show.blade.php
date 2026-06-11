@extends('layouts.public')

@section('title', $template->name)

@section('content')

<script>
    window.__formRules = @json($rules);
    window.__formTs = {
        {
            $ts
        }
    };
</script>

<div x-data="publicForm()" x-init="init()">

    {{-- Cabecera del formulario --}}
    <div class="mb-8">
        <h1 class="pf-form-heading">{{ $template->name }}</h1>
        @if($template->description)
        <p class="pf-form-description">{{ $template->description }}</p>
        @endif
    </div>

    {{-- Errores globales --}}
    @if($errors->any())
    <div class="pf-error-bag">
        <ul class="space-y-1">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form
        method="POST"
        action="{{ route('public.form.submit', $template->slug) }}"
        enctype="multipart/form-data"
        novalidate
        @submit="submitting = true">
        @csrf

        {{-- Honeypot --}}
        <input type="text" name="_hp" value="" style="display:none" tabindex="-1" autocomplete="off">
        <input type="hidden" name="_ts" :value="ts">

        {{-- Datos de contacto --}}
        <div class="pf-card mb-6">
            <h2 class="pf-section-title">{{ __('forms.public.contact_info') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                {{-- Nombre --}}
                <div class="sm:col-span-2">
                    <label for="submitter_name" class="pf-label">
                        {{ __('forms.public.submitter_name') }}
                        <span class="pf-label-required">*</span>
                    </label>
                    <input
                        type="text"
                        id="submitter_name"
                        name="submitter_name"
                        value="{{ old('submitter_name') }}"
                        required
                        class="pf-input @error('submitter_name') pf-input-error @enderror">
                    @error('submitter_name')
                    <p class="pf-field-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="submitter_email" class="pf-label">
                        {{ __('forms.public.submitter_email') }}
                        <span class="pf-label-required">*</span>
                    </label>
                    <input
                        type="email"
                        id="submitter_email"
                        name="submitter_email"
                        value="{{ old('submitter_email') }}"
                        required
                        class="pf-input @error('submitter_email') pf-input-error @enderror">
                    @error('submitter_email')
                    <p class="pf-field-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Teléfono --}}
                <div>
                    <label for="submitter_phone" class="pf-label">
                        {{ __('forms.public.submitter_phone') }}
                        <span class="pf-label-optional">({{ __('forms.public.optional') }})</span>
                    </label>
                    <input
                        type="tel"
                        id="submitter_phone"
                        name="submitter_phone"
                        value="{{ old('submitter_phone') }}"
                        class="pf-input">
                </div>

                {{-- Empresa --}}
                <div class="sm:col-span-2">
                    <label for="submitter_company" class="pf-label">
                        {{ __('forms.public.submitter_company') }}
                        <span class="pf-label-optional">({{ __('forms.public.optional') }})</span>
                    </label>
                    <input
                        type="text"
                        id="submitter_company"
                        name="submitter_company"
                        value="{{ old('submitter_company') }}"
                        class="pf-input">
                </div>

            </div>
        </div>

        {{-- Secciones y preguntas --}}
        @foreach($sections as $section)
        <div
            x-show="isSectionVisible('{{ $section->id }}')"
            x-transition
            class="pf-card mb-6"
            data-section-id="{{ $section->id }}">
            <h2 class="pf-section-title">{{ $section->title }}</h2>
            @if($section->description)
            <p class="pf-card-description mb-4">{{ $section->description }}</p>
            @endif

            <div class="space-y-6">
                @foreach($section->questions as $question)
                <div
                    x-show="isQuestionVisible('{{ $question->id }}')"
                    x-transition
                    data-question-id="{{ $question->id }}">
                    <label for="q_{{ $question->id }}" class="pf-label">
                        {{ $question->label }}
                        @if($question->is_required)
                        <span class="pf-label-required">*</span>
                        @else
                        <span class="pf-label-optional">({{ __('forms.public.optional') }})</span>
                        @endif
                    </label>

                    @if($question->help_text)
                    <p class="pf-help mb-1.5">{{ $question->help_text }}</p>
                    @endif

                    @switch($question->type->value)

                    @case('text')
                    @case('email')
                    @case('phone')
                    <input
                        type="{{ $question->type->value === 'phone' ? 'tel' : $question->type->value }}"
                        id="q_{{ $question->id }}"
                        name="answers[{{ $question->id }}]"
                        value="{{ old("answers.{$question->id}") }}"
                        placeholder="{{ $question->placeholder }}"
                        @if($question->is_required) x-bind:required="isQuestionVisible('{{ $question->id }}')" @endif
                    class="pf-input"
                    @change="updateAnswer('{{ $question->id }}', $event.target.value)"
                    >
                    @break

                    @case('number')
                    <input
                        type="number"
                        id="q_{{ $question->id }}"
                        name="answers[{{ $question->id }}]"
                        value="{{ old("answers.{$question->id}") }}"
                        placeholder="{{ $question->placeholder }}"
                        @if($question->is_required) x-bind:required="isQuestionVisible('{{ $question->id }}')" @endif
                    class="pf-input"
                    @change="updateAnswer('{{ $question->id }}', $event.target.value)"
                    >
                    @break

                    @case('textarea')
                    <textarea
                        id="q_{{ $question->id }}"
                        name="answers[{{ $question->id }}]"
                        rows="4"
                        placeholder="{{ $question->placeholder }}"
                        @if($question->is_required) x-bind:required="isQuestionVisible('{{ $question->id }}')" @endif
                                        class="pf-textarea"
                                        @change="updateAnswer('{{ $question->id }}', $event.target.value)"
                                    >{{ old("answers.{$question->id}") }}</textarea>
                    @break

                    @case('select')
                    <select
                        id="q_{{ $question->id }}"
                        name="answers[{{ $question->id }}]"
                        @if($question->is_required) x-bind:required="isQuestionVisible('{{ $question->id }}')" @endif
                        class="pf-select"
                        @change="updateAnswer('{{ $question->id }}', $event.target.value)"
                        >
                        <option value="">Seleccione una opción…</option>
                        @foreach($question->options ?? [] as $option)
                        <option
                            value="{{ $option['value'] }}"
                            {{ old("answers.{$question->id}") === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @break

                    @case('multiselect')
                    <div class="space-y-2.5">
                        @foreach($question->options ?? [] as $option)
                        <label class="pf-choice-label">
                            <input
                                type="checkbox"
                                name="answers[{{ $question->id }}][]"
                                value="{{ $option['value'] }}"
                                {{ in_array($option['value'], (array) old("answers.{$question->id}", [])) ? 'checked' : '' }}
                                class="pf-checkbox">
                            {{ $option['label'] }}
                        </label>
                        @endforeach
                    </div>
                    @break

                    @case('boolean')
                    <div class="flex gap-6">
                        <label class="pf-choice-label">
                            <input
                                type="radio"
                                name="answers[{{ $question->id }}]"
                                value="1"
                                {{ old("answers.{$question->id}") === '1' ? 'checked' : '' }}
                                class="pf-radio"
                                @change="updateAnswer('{{ $question->id }}', '1')">
                            Sí
                        </label>
                        <label class="pf-choice-label">
                            <input
                                type="radio"
                                name="answers[{{ $question->id }}]"
                                value="0"
                                {{ old("answers.{$question->id}") === '0' ? 'checked' : '' }}
                                class="pf-radio"
                                @change="updateAnswer('{{ $question->id }}', '0')">
                            No
                        </label>
                    </div>
                    @break

                    @case('date')
                    <input
                        type="date"
                        id="q_{{ $question->id }}"
                        name="answers[{{ $question->id }}]"
                        value="{{ old("answers.{$question->id}") }}"
                        @if($question->is_required) x-bind:required="isQuestionVisible('{{ $question->id }}')" @endif
                    class="pf-input"
                    @change="updateAnswer('{{ $question->id }}', $event.target.value)"
                    >
                    @break

                    @case('file')
                    <input
                        type="file"
                        id="q_{{ $question->id }}"
                        name="files[{{ $question->id }}][]"
                        multiple
                        @if($question->is_required) x-bind:required="isQuestionVisible('{{ $question->id }}')" @endif
                    class="pf-file"
                    >
                    @if(!empty($question->validation_rules['accepted_types']))
                    <p class="pf-help">
                        {{ __('forms.public.file_types', ['types' => implode(', ', (array) $question->validation_rules['accepted_types'])]) }}
                    </p>
                    @endif
                    @break

                    @endswitch

                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        {{-- Botón enviar --}}
        <div class="mt-6 flex justify-end">
            <button type="submit" :disabled="submitting" class="pf-btn-primary">
                <span x-show="submitting" class="pf-spinner"></span>
                <span x-show="!submitting">{{ __('forms.public.submit') }}</span>
                <span x-show="submitting">{{ __('forms.public.submitting') }}</span>
            </button>
        </div>

    </form>
</div>

<script>
    function publicForm() {
        return {
            answers: {},
            submitting: false,
            ts: window.__formTs,

            init() {
                document.querySelectorAll('[data-question-id]').forEach(el => {
                    const id = el.dataset.questionId;
                    const input = el.querySelector('input, select, textarea');
                    if (input) {
                        this.answers[id] = input.value || '';
                    }
                });
            },

            updateAnswer(questionId, value) {
                this.answers[questionId] = value;
            },

            isQuestionVisible(questionId) {
                return this.isTargetVisible(questionId, 'question');
            },

            isSectionVisible(sectionId) {
                return this.isTargetVisible(sectionId, 'section');
            },

            isTargetVisible(targetId, targetType) {
                const rules = (window.__formRules || []).filter(
                    r => r.target_type === targetType && r.target_id === targetId
                );

                if (rules.length === 0) return true;

                const showRules = rules.filter(r => r.action === 'show');
                const hideRules = rules.filter(r => r.action === 'hide');

                if (hideRules.some(r => this.evaluateRule(r))) return false;
                if (showRules.length > 0) return showRules.some(r => this.evaluateRule(r));

                return true;
            },

            evaluateRule(rule) {
                const actual = this.answers[rule.trigger_question_id] ?? '';
                const expected = rule.trigger_value ?? '';

                switch (rule.operator) {
                    case 'eq':
                        return String(actual) === String(expected);
                    case 'neq':
                        return String(actual) !== String(expected);
                    case 'gt':
                        return parseFloat(actual) > parseFloat(expected);
                    case 'lt':
                        return parseFloat(actual) < parseFloat(expected);
                    case 'gte':
                        return parseFloat(actual) >= parseFloat(expected);
                    case 'lte':
                        return parseFloat(actual) <= parseFloat(expected);
                    case 'contains':
                        return String(actual).includes(String(expected));
                    case 'not_contains':
                        return !String(actual).includes(String(expected));
                    case 'is_empty':
                        return actual === '' || actual === null || actual === undefined;
                    case 'is_not_empty':
                        return actual !== '' && actual !== null && actual !== undefined;
                    default:
                        return false;
                }
            },
        };
    }
</script>

@endsection