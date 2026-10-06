<?php

namespace App\Http\Requests;

use App\Support\LeadForm;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The rules come from config/lead_form.php, the single definition of the form.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return LeadForm::rules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return LeadForm::attributes();
    }
}
