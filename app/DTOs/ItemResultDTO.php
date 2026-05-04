<?php

namespace App\DTOs;

class ItemResultDTO
{
    public function __construct(
        public int $templateItemId,
        public ?string $result = null,
        public ?string $observations = null,
        public ?array $numericValue = null,
        public ?string $textValue = null,
        public bool $hasEvidence = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            templateItemId: (int) $data['template_item_id'],
            result: $data['result'] ?? null,
            observations: $data['observations'] ?? null,
            numericValue: $data['numeric_value'] ?? null,
            textValue: $data['text_value'] ?? null,
            hasEvidence: $data['has_evidence'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'template_item_id' => $this->templateItemId,
            'result' => $this->result,
            'observations' => $this->observations,
            'numeric_value' => $this->numericValue,
            'text_value' => $this->textValue,
            'has_evidence' => $this->hasEvidence,
        ];
    }

    public function isValidTernaryResult(): bool
    {
        return in_array($this->result, ['C', 'NC', 'NA']);
    }

    public function isValidNumericResult(): bool
    {
        if ($this->result !== 'C' && $this->result !== 'NC') {
            return false;
        }

        return isset($this->numericValue['value']) && is_numeric($this->numericValue['value']);
    }
}
