<?php

namespace App\Enums;

enum FormFieldType: string
{
    case Text = 'TEXT';
    case Textarea = 'TEXTAREA';
    case Email = 'EMAIL';
    case Number = 'NUMBER';
    case Date = 'DATE';
    case Select = 'SELECT';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texto corto',
            self::Textarea => 'Texto largo',
            self::Email => 'Correo',
            self::Number => 'Número',
            self::Date => 'Fecha',
            self::Select => 'Lista de opciones',
        };
    }

    public function inputType(): string
    {
        return match ($this) {
            self::Email => 'email',
            self::Number => 'number',
            self::Date => 'date',
            default => 'text',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
