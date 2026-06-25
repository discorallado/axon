<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pendiente = 'pendiente';
    case EnProgreso = 'en_progreso';
    case EnRevision = 'en_revision';
    case Completada = 'completada';
    case Bloqueada = 'bloqueada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnProgreso => 'En Progreso',
            self::EnRevision => 'En Revisión',
            self::Completada => 'Completada',
            self::Bloqueada => 'Bloqueada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::EnProgreso => 'info',
            self::EnRevision => 'warning',
            self::Completada => 'success',
            self::Bloqueada => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pendiente => 'heroicon-o-clock',
            self::EnProgreso => 'heroicon-o-play',
            self::EnRevision => 'heroicon-o-eye',
            self::Completada => 'heroicon-o-check-circle',
            self::Bloqueada => 'heroicon-o-x-circle',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completada;
    }
}
