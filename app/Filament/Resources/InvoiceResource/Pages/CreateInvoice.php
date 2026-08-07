<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
