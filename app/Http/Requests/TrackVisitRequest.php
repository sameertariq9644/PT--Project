<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackVisitRequest extends FormRequest
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
            'ref' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/'],
            'vid' => ['required', 'uuid'],
            'campaign' => ['nullable', 'string', 'max:80'],
            'path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
