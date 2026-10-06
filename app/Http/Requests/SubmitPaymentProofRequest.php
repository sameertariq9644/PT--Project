<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class SubmitPaymentProofRequest extends FormRequest
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
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'transferred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:tomorrow', 'after:-1 year'],
            // Checked against the file's real content, not its name or the MIME type the browser claims.
            'receipt' => ['required', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(5 * 1024)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'transaction_id' => 'transaction ID',
            'transferred_on' => 'transfer date',
        ];
    }
}
