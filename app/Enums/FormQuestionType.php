<?php

namespace App\Enums;

enum FormQuestionType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Boolean = 'boolean';
    case Date = 'date';
    case File = 'file';
    case Email = 'email';
    case Phone = 'phone';

    public function label(): string
    {
        return __('forms.question.types.'.$this->value);
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Multiselect]);
    }

    public function isFile(): bool
    {
        return $this === self::File;
    }

    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
