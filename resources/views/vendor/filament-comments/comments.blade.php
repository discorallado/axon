<div class="flex flex-col h-full space-y-4">
    @if (auth()->user()->can('create', \Parallax\FilamentComments\Models\FilamentComment::class))
        <div class="space-y-4" x-data x-init="$nextTick(() => $nextTick(() => window.axonInitMentions?.()))">
            {{ $this->form }}

            <x-filament::button
                wire:click="create"
                color="primary"
            >
                {{ __('filament-comments::filament-comments.comments.add') }}
            </x-filament::button>
        </div>
    @endif

    @if (count($comments))
        <div class="gap-4">
            @foreach ($comments as $comment)
                <div class="fi-in-repeatable-item mb-3 block rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="flex gap-x-3">
                        @if (config('filament-comments.display_avatars'))
                            <x-filament-panels::avatar.user size="md" :user="$comment->user" />
                        @endif

                        <div class="flex-grow space-y-2 pt-[6px]">
                            <div class="flex items-center justify-between gap-x-2">
                                <div class="flex items-center gap-x-2">
                                    <div class="text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $comment->user[config('filament-comments.user_name_attribute')] }}
                                    </div>

                                    <div class="text-xs font-medium text-gray-400 dark:text-gray-500">
                                        {{ $comment->created_at->diffForHumans() }}
                                    </div>
                                </div>

                                @if (auth()->user()->can('delete', $comment))
                                    <div class="flex-shrink-0">
                                        <x-filament::icon-button
                                            wire:click="delete({{ $comment->id }})"
                                            icon="{{ config('filament-comments.icons.delete') }}"
                                            color="danger"
                                            tooltip="{{ __('filament-comments::filament-comments.comments.delete.tooltip') }}"
                                        />
                                    </div>
                                @endif
                            </div>

                            <div class="prose prose-sm dark:prose-invert [&>*]:mb-2 [&>*]:mt-0 [&>*:last-child]:mb-0 text-sm leading-6 text-gray-950 dark:text-white">
                                @if(config('filament-comments.editor') === 'markdown')
                                    {{ Str::of($comment->comment)->markdown()->toHtmlString() }}
                                @else
                                    {!! $comment->comment !!}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex h-full flex-col items-center justify-center space-y-4">
            <x-filament::icon
                icon="{{ config('filament-comments.icons.empty') }}"
                class="h-12 w-12 text-gray-400 dark:text-gray-500"
            />

            <div class="text-sm text-gray-400 dark:text-gray-500">
                {{ __('filament-comments::filament-comments.comments.empty') }}
            </div>
        </div>
    @endif

    <x-filament-actions::modals />
</div>

@once
@assets
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.min.css">
<script src="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.min.js"></script>
<style>
    .tribute-container { z-index: 9999; }
    .tribute-container ul { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.25rem; box-shadow: 0 4px 12px rgba(0,0,0,.12); min-width: 180px; }
    .dark .tribute-container ul { background: #1f2937; border-color: #374151; }
    .tribute-container li { padding: 0.4rem 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; cursor: pointer; color: #111827; }
    .dark .tribute-container li { color: #f9fafb; }
    .tribute-container li.highlight, .tribute-container li:hover { background: rgba(59,130,246,.15); }
    .mention { color: rgb(59 130 246); font-weight: 600; }
</style>
<script>
window.axonInitMentions = function () {
    const editors = document.querySelectorAll(
        '.fi-fo-rich-editor .ProseMirror, .fi-fo-rich-editor [contenteditable="true"]'
    );
    editors.forEach(function (el) {
        if (el._axonTribute) return;
        const tribute = new Tribute({
            trigger: '@',
            allowSpaces: false,
            lookup: 'name',
            fillAttr: 'name',
            values: function (text, cb) {
                fetch('/admin/api/mention-suggestions?query=' + encodeURIComponent(text), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                })
                    .then(function (r) { return r.json(); })
                    .then(cb)
                    .catch(function () { cb([]); });
            },
            menuItemTemplate: function (item) {
                return '<span>' + item.original.name + '</span>';
            },
            selectTemplate: function (item) {
                return (
                    '<span class="mention"' +
                    ' data-user-id="' + item.original.id + '"' +
                    ' data-user-name="' + item.original.name + '"' +
                    ' contenteditable="false">@' + item.original.name + '</span>'
                );
            },
        });
        tribute.attach(el);
        el._axonTribute = tribute;
    });
};

document.addEventListener('livewire:initialized', function () { window.axonInitMentions?.(); });
document.addEventListener('livewire:navigated', function () { setTimeout(window.axonInitMentions, 300); });
</script>
@endassets
@endonce
