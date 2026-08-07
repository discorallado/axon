<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PurchaseOrderStatus: string implements HasColor, HasIcon, HasLabel
{
    case Borrador = 'borrador';
    case Emitida = 'emitida';
    case Recibida = 'recibida';
    case Anulada = 'anulada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Emitida => 'Emitida',
            self::Recibida => 'Recibida',
            self::Anulada => 'Anulada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Borrador => 'gray',
            self::Emitida => 'info',
            self::Recibida => 'success',
            self::Anulada => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Borrador => 'heroicon-o-pencil',
            self::Emitida => 'heroicon-o-paper-airplane',
            self::Recibida => 'heroicon-o-check-circle',
            self::Anulada => 'heroicon-o-x-circle',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Recibida, self::Anulada]);
    }
}
