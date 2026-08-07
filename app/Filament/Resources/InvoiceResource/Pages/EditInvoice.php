<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($missing = InvoiceResource::missingRequiredTypeField($data)) {
            Notification::make()
                ->danger()
                ->title(__('invoices.errors.missing_required_field'))
                ->body(__("invoices.fields.{$missing}"))
                ->send();

            $this->halt();
        }

        $data = InvoiceResource::normalizeTypeFields($data);

        return InvoiceResource::recalculateAmountTotal($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
