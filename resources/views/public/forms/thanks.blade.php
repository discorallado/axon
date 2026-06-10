@extends('layouts.public')

@section('title', __('forms.thanks.title'))

@section('content')
<div class="text-center py-10">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-6">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-3">
        {{ __('forms.thanks.title') }}
    </h1>

    <p class="text-gray-600 mb-4">
        {{ __('forms.thanks.message') }}
    </p>

    @if($reference)
        <div class="inline-block rounded-lg bg-indigo-50 border border-indigo-100 px-6 py-3 mb-4">
            <p class="text-sm font-semibold text-indigo-700">
                {{ __('forms.thanks.reference', ['code' => $reference]) }}
            </p>
        </div>
    @endif

    @if($email)
        <p class="text-sm text-gray-500">
            {{ __('forms.thanks.email_sent', ['email' => $email]) }}
        </p>
    @endif
</div>
@endsection
