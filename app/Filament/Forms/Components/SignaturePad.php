<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Contracts\View\View;

class SignaturePad extends Field
{
    protected string $view = 'filament.forms.components.signature-pad';

    protected ?string $signerName = null;
    
    protected ?string $signerTitle = null;

    protected bool $allowExternalSignature = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);
    }

    public function signerName(?string $name): static
    {
        $this->signerName = $name;
        
        return $this;
    }

    public function getSignerName(): ?string
    {
        return $this->signerName;
    }

    public function signerTitle(?string $title): static
    {
        $this->signerTitle = $title;
        
        return $this;
    }

    public function getSignerTitle(): ?string
    {
        return $this->signerTitle;
    }

    public function allowExternalSignature(bool $condition = true): static
    {
        $this->allowExternalSignature = $condition;
        
        return $this;
    }

    public function isExternalSignatureAllowed(): bool
    {
        return $this->allowExternalSignature;
    }
}
