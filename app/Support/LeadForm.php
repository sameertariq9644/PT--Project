<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Reads the lead form definition in config/lead_form.php, so the public form,
 * its validation, the admin table and the notification email stay in sync.
 */
class LeadForm
{
    /**
     * Fields stored in their own `leads` columns; the rest go into `answers`.
     *
     * @var list<string>
     */
    public const CORE = ['name', 'email', 'whatsapp'];

    /**
     * Every field, with defaults filled in and options normalised to value => label.
     *
     * @return list<array{key: string, label: string, type: string, options: array<string, string>, placeholder: ?string, rules: list<mixed>, width: string, in_table: bool, table_label: string, table_width: string, default: ?string}>
     */
    public static function fields(): array
    {
        return array_map(function (array $field): array {
            $options = [];

            foreach ($field['options'] ?? [] as $value => $label) {
                $options[(string) (is_int($value) ? $label : $value)] = (string) $label;
            }

            return [
                'key' => $field['key'],
                'label' => $field['label'],
                'type' => $field['type'] ?? 'text',
                'options' => $options,
                'placeholder' => $field['placeholder'] ?? null,
                'rules' => $field['rules'] ?? ['nullable'],
                'width' => $field['width'] ?? 'half',
                'in_table' => (bool) ($field['in_table'] ?? false),
                'table_label' => $field['table_label'] ?? $field['label'],
                'table_width' => $field['table_width'] ?? '1fr',
                'default' => $field['default'] ?? null,
            ];
        }, config('lead_form.fields', []));
    }

    /**
     * Validation rules keyed by field.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (static::fields() as $field) {
            $rules[$field['key']] = $field['options'] === []
                ? $field['rules']
                : [...$field['rules'], Rule::in(array_keys($field['options']))];
        }

        return $rules;
    }

    /**
     * Human-readable attribute names for validation messages.
     *
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return array_column(static::fields(), 'label', 'key');
    }

    /**
     * The fields saved into `leads.answers` (everything except the core columns).
     *
     * @return list<array<string, mixed>>
     */
    public static function answerFields(): array
    {
        return array_values(array_filter(
            static::fields(),
            fn (array $field): bool => ! in_array($field['key'], self::CORE, true),
        ));
    }

    /**
     * The answer fields shown as columns in the admin leads table.
     *
     * @return list<array<string, mixed>>
     */
    public static function tableColumns(): array
    {
        return array_values(array_filter(static::answerFields(), fn (array $field): bool => $field['in_table']));
    }

    /**
     * Split validated input into the core columns and the answers.
     *
     * @param  array<string, mixed>  $validated
     * @return array{name: string, email: string, whatsapp: string, answers: array<string, mixed>}
     */
    public static function split(array $validated): array
    {
        $answers = [];

        foreach (static::answerFields() as $field) {
            $answers[$field['key']] = $validated[$field['key']] ?? null;
        }

        return [
            'name' => trim((string) $validated['name']),
            'email' => mb_strtolower(trim((string) $validated['email'])),
            'whatsapp' => trim((string) $validated['whatsapp']),
            'answers' => $answers,
        ];
    }
}
