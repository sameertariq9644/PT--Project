<?php

namespace App\Http\Requests\Admin;

use App\Models\Affiliate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SaveAffiliateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Used for both creating and editing. The password is required when
     * creating and optional (blank keeps the current one) when editing.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Affiliate|null $affiliate */
        $affiliate = $this->route('affiliate');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('users', 'email')->ignore($affiliate?->user_id)],
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($affiliate?->user_id)],
            'password' => [$affiliate ? 'nullable' : 'required', Password::min(8)],
            'code' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('affiliates', 'code')->ignore($affiliate?->id)],
            'channel' => ['nullable', 'string', 'max:80'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['code' => 'referral code'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.regex' => 'The username may only contain lowercase letters, numbers, dots, dashes and underscores.',
            'code.regex' => 'The referral code may only contain letters, numbers, dashes and underscores.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
