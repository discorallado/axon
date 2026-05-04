<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Contracts\View\View;

class TernaryResultField extends Field
{
    protected string $view = 'filament.forms.components.ternary-result-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);
    }

    public function getResultColor(?string $state): string
    {
        return match($state) {
            'C' => 'success',
            'NC' => 'danger',
            'NA' => 'gray',
            default => 'secondary',
        };
    }

    public function getResultLabel(?string $state): string
    {
        return match($state) {
            'C' => 'Conforme',
            'NC' => 'No Conforme',
            'NA' => 'No Aplica',
            default => 'Sin evaluar',
        };
    }

    public function getResultSymbol(?string $state): string
    {
        return match($state) {
            'C' => '✓',
            'NC' => '✗',
            'NA' => '–',
            default => '○',
        };
    }
}
