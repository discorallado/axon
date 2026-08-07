<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pendiente = 'pendiente';
    case Pagada = 'pagada';
    case Vencida = 'vencida';
    case Anulada = 'anulada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Pagada => 'Pagada',
            self::Vencida => 'Vencida',
            self::Anulada => 'Anulada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Pagada => 'success',
            self::Vencida => 'danger',
            self::Anulada => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pendiente => 'heroicon-o-clock',
            self::Pagada => 'heroicon-o-check-circle',
            self::Vencida => 'heroicon-o-exclamation-triangle',
            self::Anulada => 'heroicon-o-x-circle',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Pagada, self::Anulada]);
    }
}
