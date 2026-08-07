<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InvoiceType: string implements HasColor, HasIcon, HasLabel
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';

    public function getLabel(): string
    {
        return match ($this) {
            self::Incoming => 'Entrada (proveedor)',
            self::Outgoing => 'Salida (cliente)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Incoming => 'warning',
            self::Outgoing => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Incoming => 'heroicon-o-arrow-down-tray',
            self::Outgoing => 'heroicon-o-arrow-up-tray',
        };
    }
}
