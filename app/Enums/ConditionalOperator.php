<?php

namespace App\Enums;

enum ConditionalOperator: string
{
    case Eq = 'eq';
    case Neq = 'neq';
    case Gt = 'gt';
    case Lt = 'lt';
    case Gte = 'gte';
    case Lte = 'lte';
    case Contains = 'contains';
    case NotContains = 'not_contains';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';

    public function label(): string
    {
        return __('forms.rule.operators.'.$this->value);
    }

    public function needsValue(): bool
    {
        return ! in_array($this, [self::IsEmpty, self::IsNotEmpty]);
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return match ($this) {
            self::Eq => (string) $actual === (string) $expected,
            self::Neq => (string) $actual !== (string) $expected,
            self::Gt => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            self::Lt => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            self::Gte => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            self::Lte => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            self::Contains => str_contains((string) $actual, (string) $expected),
            self::NotContains => ! str_contains((string) $actual, (string) $expected),
            self::IsEmpty => $actual === null || $actual === '' || $actual === [],
            self::IsNotEmpty => $actual !== null && $actual !== '' && $actual !== [],
        };
    }

    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
