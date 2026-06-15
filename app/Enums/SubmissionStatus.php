<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SubmissionStatus: string implements HasColor, HasIcon, HasLabel
{
    case Nueva = 'nueva';
    case EnRevision = 'en_revision';
    case Cotizada = 'cotizada';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Nueva => 'Nueva',
            self::EnRevision => 'En revisión',
            self::Cotizada => 'Cotizada',
            self::Aprobada => 'Aprobada',
            self::Rechazada => 'Rechazada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Nueva => 'info',
            self::EnRevision => 'warning',
            self::Cotizada => 'primary',
            self::Aprobada => 'success',
            self::Rechazada => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Nueva => 'heroicon-o-inbox',
            self::EnRevision => 'heroicon-o-magnifying-glass',
            self::Cotizada => 'heroicon-o-document-text',
            self::Aprobada => 'heroicon-o-check-circle',
            self::Rechazada => 'heroicon-o-x-circle',
        };
    }

    public function isInitial(): bool
    {
        return $this === self::Nueva;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Aprobada, self::Rechazada]);
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Nueva => 1,
            self::EnRevision => 2,
            self::Cotizada => 3,
            self::Aprobada => 4,
            self::Rechazada => 5,
        };
    }
}
