@extends('layouts.public')

@section('title', $template->name)

@section('content')

{{-- Serialización de reglas condicionales para Alpine.js --}}
<script>
    window.__formRules = @json($rules);
    window.__formTs    = {{ $ts }};
</script>

<div
    x-data="publicForm()"
    x-init="init()"
>
    {{-- Cabecera --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">{{ $template->name }}</h1>
        @if($template->description)
            <p class="mt-2 text-gray-600">{{ $template->description }}</p>
        @endif
    </div>

    {{-- Errores globales --}}
    @if($errors->any())
        <div class="mb-6 rounded-md bg-red-50 border border-red-200 p-4">
            <ul class="text-sm text-red-700 space-y-1">
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
    >
        @csrf

        {{-- Campos anti-spam --}}
        <input type="text" name="_hp" value="" style="display:none" tabindex="-1" autocomplete="off">
        <input type="hidden" name="_ts" :value="ts">

        {{-- Datos de contacto --}}
        <div class="mb-8 rounded-xl bg-white border border-gray-200 p-6 shadow-sm space-y-4">
            <h2 class="text-base font-semibold text-gray-800">
                {{ __('forms.public.contact_info') }}
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Nombre --}}
                <div class="sm:col-span-2">
                    <label for="submitter_name" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('forms.public.submitter_name') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="submitter_name"
                        name="submitter_name"
                        value="{{ old('submitter_name') }}"
                        required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm @error('submitter_name') border-red-400 @enderror"
                    >
                    @error('submitter_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="submitter_email" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('forms.public.submitter_email') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="email"
                        id="submitter_email"
                        name="submitter_email"
                        value="{{ old('submitter_email') }}"
                        required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm @error('submitter_email') border-red-400 @enderror"
                    >
                    @error('submitter_email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Teléfono --}}
                <div>
                    <label for="submitter_phone" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('forms.public.submitter_phone') }}
                    </label>
                    <input
                        type="tel"
                        id="submitter_phone"
                        name="submitter_phone"
                        value="{{ old('submitter_phone') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                    >
                </div>

                {{-- Empresa --}}
                <div class="sm:col-span-2">
                    <label for="submitter_company" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('forms.public.submitter_company') }}
                    </label>
                    <input
                        type="text"
                        id="submitter_company"
                        name="submitter_company"
                        value="{{ old('submitter_company') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                    >
                </div>
            </div>
        </div>

        {{-- Secciones y preguntas --}}
        @foreach($sections as $section)
            <div
                x-show="isSectionVisible('{{ $section->id }}')"
                x-transition
                class="mb-6 rounded-xl bg-white border border-gray-200 p-6 shadow-sm"
                data-section-id="{{ $section->id }}"
            >
                <h2 class="text-base font-semibold text-gray-800 mb-4">{{ $section->title }}</h2>
                @if($section->description)
                    <p class="text-sm text-gray-500 mb-4">{{ $section->description }}</p>
                @endif

                <div class="space-y-5">
                    @foreach($section->questions as $question)
                        <div
                            x-show="isQuestionVisible('{{ $question->id }}')"
                            x-transition
                            data-question-id="{{ $question->id }}"
                        >
                            <label
                                for="q_{{ $question->id }}"
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                {{ $question->label }}
                                @if($question->is_required)
                                    <span class="text-red-500">*</span>
                                @else
                                    <span class="text-gray-400 text-xs font-normal">({{ __('forms.public.optional') }})</span>
                                @endif
                            </label>

                            @if($question->help_text)
                                <p class="text-xs text-gray-500 mb-1">{{ $question->help_text }}</p>
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
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
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
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
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
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                        @change="updateAnswer('{{ $question->id }}', $event.target.value)"
                                    >{{ old("answers.{$question->id}") }}</textarea>
                                    @break

                                @case('select')
                                    <select
                                        id="q_{{ $question->id }}"
                                        name="answers[{{ $question->id }}]"
                                        @if($question->is_required) x-bind:required="isQuestionVisible('{{ $question->id }}')" @endif
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                        @change="updateAnswer('{{ $question->id }}', $event.target.value)"
                                    >
                                        <option value="">Seleccione una opción…</option>
                                        @foreach($question->options ?? [] as $option)
                                            <option
                                                value="{{ $option['value'] }}"
                                                {{ old("answers.{$question->id}") === $option['value'] ? 'selected' : '' }}
                                            >{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('multiselect')
                                    <div class="space-y-2">
                                        @foreach($question->options ?? [] as $option)
                                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    name="answers[{{ $question->id }}][]"
                                                    value="{{ $option['value'] }}"
                                                    {{ in_array($option['value'], (array) old("answers.{$question->id}", [])) ? 'checked' : '' }}
                                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                >
                                                {{ $option['label'] }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @break

                                @case('boolean')
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                                            <input
                                                type="radio"
                                                name="answers[{{ $question->id }}]"
                                                value="1"
                                                {{ old("answers.{$question->id}") === '1' ? 'checked' : '' }}
                                                class="border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                @change="updateAnswer('{{ $question->id }}', '1')"
                                            > Sí
                                        </label>
                                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                                            <input
                                                type="radio"
                                                name="answers[{{ $question->id }}]"
                                                value="0"
                                                {{ old("answers.{$question->id}") === '0' ? 'checked' : '' }}
                                                class="border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                @change="updateAnswer('{{ $question->id }}', '0')"
                                            > No
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
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                        @change="updateAnswer('{{ $question->id }}', $event.target.value)"
                                    >
                                    @break

                                @case('file')
                                    <input
                                        type="file"
                                        id="q_{{ $question->id }}"
                                        name="files[{{ $question->id }}]"
                                        @if($question->is_required) x-bind:required="isQuestionVisible('{{ $question->id }}')" @endif
                                        class="w-full text-sm text-gray-600 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    >
                                    @if(!empty($question->validation_rules['accepted_types']))
                                        <p class="mt-1 text-xs text-gray-400">
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

        {{-- Enviar --}}
        <div class="mt-6 flex justify-end">
            <button
                type="submit"
                :disabled="submitting"
                @click="submitting = true"
                class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed transition"
            >
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
            // Poblar respuestas con valores old() de Laravel si existen
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

            // Si hay reglas "show": mostrar si alguna se cumple
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
                case 'eq':          return String(actual) === String(expected);
                case 'neq':         return String(actual) !== String(expected);
                case 'gt':          return parseFloat(actual) > parseFloat(expected);
                case 'lt':          return parseFloat(actual) < parseFloat(expected);
                case 'gte':         return parseFloat(actual) >= parseFloat(expected);
                case 'lte':         return parseFloat(actual) <= parseFloat(expected);
                case 'contains':    return String(actual).includes(String(expected));
                case 'not_contains':return !String(actual).includes(String(expected));
                case 'is_empty':    return actual === '' || actual === null || actual === undefined;
                case 'is_not_empty':return actual !== '' && actual !== null && actual !== undefined;
                default:            return false;
            }
        },
    };
}
</script>

@endsection
