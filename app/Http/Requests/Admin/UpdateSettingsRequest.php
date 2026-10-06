<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The form posts nested arrays (`mail[host]`), validated here with dot
     * notation and stored flattened (`mail.host`).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $recipients = ['nullable', 'string', 'max:500', function (string $attribute, mixed $value, \Closure $fail): void {
            foreach (preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY) as $address) {
                if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
                    $fail('"'.$address.'" is not a valid email address.');
                }
            }
        }];

        return [
            'notifications.lead_recipients' => $recipients,
            'notifications.order_recipients' => $recipients,

            'mail.from_name' => ['nullable', 'string', 'max:100'],
            'mail.from_address' => ['nullable', 'string', 'email', 'max:190'],
            'mail.host' => ['nullable', 'string', 'max:190'],
            'mail.port' => ['nullable', 'integer', 'between:1,65535'],
            'mail.encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
            'mail.username' => ['nullable', 'string', 'max:190'],
            'mail.password' => ['nullable', 'string', 'max:255'],

            'payment.bank_name' => ['nullable', 'string', 'max:120'],
            'payment.account_title' => ['nullable', 'string', 'max:120'],
            'payment.account_number' => ['nullable', 'string', 'max:60'],
            'payment.iban' => ['nullable', 'string', 'max:60'],
            'payment.instructions' => ['nullable', 'string', 'max:1000'],

            'contact.whatsapp' => ['nullable', 'string', 'max:30'],
            'contact.email' => ['nullable', 'string', 'email', 'max:190'],

            'content.intro_video_url' => ['nullable', 'url:http,https', 'max:500'],
            'content.lead_video_url' => ['nullable', 'url:http,https', 'max:500'],

            'affiliates.default_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'affiliates.attribution_days' => ['required', 'integer', 'between:1,365'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'notifications.lead_recipients' => 'lead recipients',
            'notifications.order_recipients' => 'order recipients',
            'mail.from_name' => 'from name',
            'mail.from_address' => 'from address',
            'mail.host' => 'SMTP host',
            'mail.port' => 'SMTP port',
            'mail.encryption' => 'encryption',
            'mail.username' => 'SMTP username',
            'mail.password' => 'SMTP password',
            'contact.whatsapp' => 'WhatsApp number',
            'contact.email' => 'public email',
            'content.intro_video_url' => 'intro video URL',
            'content.lead_video_url' => 'lead video URL',
            'affiliates.default_commission_rate' => 'default commission rate',
            'affiliates.attribution_days' => 'attribution window',
        ];
    }

    /**
     * The settings to store, keyed like `mail.host`. A blank SMTP password
     * means "keep the saved one", so it is left out.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $settings = [];

        foreach (array_keys($this->rules()) as $key) {
            $settings[$key] = Arr::get($this->validated(), $key);
        }

        if (blank($settings['mail.password'])) {
            unset($settings['mail.password']);
        }

        return $settings;
    }
}
