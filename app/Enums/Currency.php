<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Currency: string implements HasLabel
{
    case CLP = 'CLP';
    case USD = 'USD';
    case EUR = 'EUR';

    public function getLabel(): string
    {
        return match ($this) {
            self::CLP => 'CLP — Peso chileno',
            self::USD => 'USD — Dólar',
            self::EUR => 'EUR — Euro',
        };
    }
}
