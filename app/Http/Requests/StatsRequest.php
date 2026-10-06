<?php

namespace App\Http\Requests;

use App\Support\AffiliateStats;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatsRequest extends FormRequest
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
            'range' => ['nullable', Rule::in(AffiliateStats::RANGES)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'affiliate' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function range(): string
    {
        return $this->validated('range') ?? '30d';
    }
}
