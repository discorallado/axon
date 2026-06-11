@extends('layouts.public')

@section('title', __('forms.thanks.title'))

@section('content')
<div class="flex flex-col items-center py-16 text-center">

    <div class="flex h-16 w-16 items-center justify-center rounded-full border border-green-500/30 bg-green-500/10 mb-6">
        <svg class="h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h1 class="text-2xl font-semibold text-zinc-100 mb-3">
        {{ __('forms.thanks.title') }}
    </h1>

    <p class="text-sm text-zinc-400 mb-8 max-w-sm leading-relaxed">
        {{ __('forms.thanks.message') }}
    </p>

    @if($reference)
        <div class="pf-reference-badge mb-4">
            <svg class="h-4 w-4 text-primary-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5" />
            </svg>
            <span class="pf-reference-code">
                {{ __('forms.thanks.reference', ['code' => $reference]) }}
            </span>
        </div>
    @endif

    @if($email)
        <p class="text-xs text-zinc-500">
            {{ __('forms.thanks.email_sent', ['email' => $email]) }}
        </p>
    @endif

</div>
@endsection
