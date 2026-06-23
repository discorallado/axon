<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskPriority: string implements HasColor, HasIcon, HasLabel
{
    case Baja = 'baja';
    case Media = 'media';
    case Alta = 'alta';
    case Critica = 'critica';

    public function getLabel(): string
    {
        return match ($this) {
            self::Baja => 'Baja',
            self::Media => 'Media',
            self::Alta => 'Alta',
            self::Critica => 'Crítica',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Baja => 'gray',
            self::Media => 'info',
            self::Alta => 'warning',
            self::Critica => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Baja => 'heroicon-o-arrow-down',
            self::Media => 'heroicon-o-minus',
            self::Alta => 'heroicon-o-arrow-up',
            self::Critica => 'heroicon-o-fire',
        };
    }
}
