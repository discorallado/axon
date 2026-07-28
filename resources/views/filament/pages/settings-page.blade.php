<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Apariencia --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">
                {{ __('settings.appearance.title') }}
            </h2>

            {{-- Color de tema --}}
            <div class="mb-6">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('settings.appearance.theme_color') }}
                </label>
                <div class="flex flex-wrap gap-3">
                    @foreach ($this->availableColors() as $key => $label)
                        @php
                            $swatches = [
                                'blue'    => '#3b82f6', 'indigo'  => '#6366f1', 'violet' => '#8b5cf6',
                                'purple'  => '#a855f7', 'fuchsia' => '#d946ef', 'rose'   => '#f43f5e',
                                'red'     => '#ef4444', 'orange'  => '#f97316', 'amber'  => '#f59e0b',
                                'lime'    => '#84cc16', 'green'   => '#22c55e', 'teal'   => '#14b8a6',
                                'cyan'    => '#06b6d4', 'sky'     => '#0ea5e9',
                            ];
                        @endphp
                        <button
                            wire:click="$set('theme_color', '{{ $key }}')"
                            title="{{ $label }}"
                            class="h-8 w-8 rounded-full border-2 transition-all {{ $theme_color === $key ? 'scale-110 border-gray-900 dark:border-white' : 'border-transparent hover:scale-105' }}"
                            style="background-color: {{ $swatches[$key] ?? '#3b82f6' }}"
                        ></button>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('settings.colors.' . $theme_color) }}
                </p>
            </div>

            {{-- Estilo de navegación --}}
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('settings.appearance.nav_style') }}
                </label>
                <div class="flex gap-3">
                    @foreach (['sidebar' => __('settings.appearance.nav_sidebar'), 'topbar' => __('settings.appearance.nav_topbar')] as $val => $lbl)
                        <button
                            wire:click="$set('nav_style', '{{ $val }}')"
                            @class([
                                'flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition-colors',
                                'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' => $nav_style === $val,
                                'border-gray-300 text-gray-600 hover:border-gray-400 dark:border-gray-600 dark:text-gray-300' => $nav_style !== $val,
                            ])
                        >{{ $lbl }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Notificaciones --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">
                {{ __('settings.notifications.title') }}
            </h2>

            <div class="space-y-4">
                <label class="flex cursor-pointer items-center justify-between">
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('settings.notifications.email') }}
                    </span>
                    <button
                        wire:click="$toggle('notify_email')"
                        @class([
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                            'bg-primary-500' => $notify_email,
                            'bg-gray-200 dark:bg-gray-700' => ! $notify_email,
                        ])
                        role="switch"
                        aria-checked="{{ $notify_email ? 'true' : 'false' }}"
                    >
                        <span
                            @class([
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                'translate-x-5' => $notify_email,
                                'translate-x-0' => ! $notify_email,
                            ])
                        ></span>
                    </button>
                </label>

                <label class="flex cursor-pointer items-center justify-between">
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('settings.notifications.database') }}
                    </span>
                    <button
                        wire:click="$toggle('notify_database')"
                        @class([
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                            'bg-primary-500' => $notify_database,
                            'bg-gray-200 dark:bg-gray-700' => ! $notify_database,
                        ])
                        role="switch"
                        aria-checked="{{ $notify_database ? 'true' : 'false' }}"
                    >
                        <span
                            @class([
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                'translate-x-5' => $notify_database,
                                'translate-x-0' => ! $notify_database,
                            ])
                        ></span>
                    </button>
                </label>
            </div>
        </div>
    </div>

    {{-- Botón guardar --}}
    <div class="mt-6 flex justify-end">
        <x-filament::button wire:click="save" size="lg">
            {{ __('filament-panels::pages/auth/edit-profile.form.actions.save.label') }}
        </x-filament::button>
    </div>
</x-filament-panels::page>
