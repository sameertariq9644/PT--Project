<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'kicker' => ['required', 'string', 'max:80'],
            'teaser' => ['required', 'string', 'max:300'],
            'description' => ['required', 'string', 'max:300'],
            'price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'highlights' => ['nullable', 'string', 'max:1000'],
            'features' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * The validated data with the one-per-line textareas turned into lists.
     *
     * @return array<string, mixed>
     */
    public function planAttributes(): array
    {
        $lines = fn (?string $text): array => array_values(array_filter(
            array_map('trim', preg_split('/\R/', (string) $text)),
            fn (string $line): bool => $line !== '',
        ));

        return [
            ...$this->validated(),
            'highlights' => $lines($this->validated('highlights')),
            'features' => $lines($this->validated('features')),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
