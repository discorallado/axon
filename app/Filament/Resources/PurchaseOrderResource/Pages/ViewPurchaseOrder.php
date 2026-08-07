<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderResource;
use App\Models\Attachment;
use App\Services\PurchaseOrderStateMachine;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('upload_attachment')
                ->label(__('purchase_orders.actions.upload_attachment'))
                ->icon('heroicon-o-paper-clip')
                ->color('gray')
                ->form([
                    FileUpload::make('file')
                        ->label(__('purchase_orders.fields.attachment_file'))
                        ->disk('local')
                        ->directory('attachments/purchase-orders/'.$this->record->id)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $path = $data['file'];

                    Attachment::create([
                        'organization_id' => $this->record->organization_id,
                        'attachable_type' => 'purchase_order',
                        'attachable_id' => $this->record->id,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => basename($path),
                        'mime_type' => Storage::disk('local')->mimeType($path),
                        'size_bytes' => Storage::disk('local')->size($path),
                        'uploaded_by' => auth()->id(),
                        'tag' => 'document',
                    ]);

                    Notification::make()->title('Archivo adjuntado.')->success()->send();
                })
                ->visible(fn () => auth()->user()->can('update', $this->record)),

            Action::make('delete_attachments')
                ->label(__('purchase_orders.actions.delete_attachments'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->form(function () {
                    $attachments = $this->record->attachments;

                    if ($attachments->isEmpty()) {
                        return [
                            Placeholder::make('no_attachments')
                                ->label('')
                                ->content('Esta orden de compra no tiene adjuntos.'),
                        ];
                    }

                    return [
                        CheckboxList::make('attachment_ids')
                            ->label('Adjuntos a eliminar')
                            ->options($attachments->mapWithKeys(fn ($a) => [$a->id => $a->original_name])),
                    ];
                })
                ->action(function (array $data): void {
                    if (empty($data['attachment_ids'] ?? [])) {
                        return;
                    }

                    $toDelete = Attachment::withoutGlobalScopes()
                        ->whereIn('id', $data['attachment_ids'])
                        ->where('attachable_type', 'purchase_order')
                        ->where('attachable_id', $this->record->id)
                        ->get();

                    foreach ($toDelete as $attachment) {
                        if (Storage::disk($attachment->disk)->exists($attachment->path)) {
                            Storage::disk($attachment->disk)->delete($attachment->path);
                        }
                        $attachment->delete();
                    }

                    Notification::make()->title('Adjuntos eliminados.')->success()->send();
                })
                ->visible(fn () => auth()->user()->can('update', $this->record)),

            Action::make('change_status')
                ->label(__('purchase_orders.actions.change_status'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    Select::make('status')
                        ->label(__('purchase_orders.fields.status'))
                        ->options(function () {
                            $machine = app(PurchaseOrderStateMachine::class);

                            return collect($machine->allowedNextStatuses($this->record))
                                ->mapWithKeys(fn (PurchaseOrderStatus $s) => [$s->value => $s->getLabel()])
                                ->all();
                        })
                        ->required(),

                    Textarea::make('comment')
                        ->label('Comentario (opcional)')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $toStatus = PurchaseOrderStatus::from($data['status']);
                    $machine = app(PurchaseOrderStateMachine::class);

                    DB::transaction(function () use ($machine, $toStatus, $data): void {
                        $machine->transition(auth()->user(), $this->record, $toStatus, $data['comment'] ?? null);
                    });

                    Notification::make()->title('Estado actualizado.')->success()->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->visible(fn () => auth()->user()->can('changeStatus', $this->record)
                    && ! $this->record->status->isTerminal()),
        ];
    }
}
