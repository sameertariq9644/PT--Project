<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lead (Eligibility) Form
    |--------------------------------------------------------------------------
    |
    | The one definition of the lead form. The public form, its validation,
    | the admin leads table, the lead detail page, the CSV export and the
    | notification email are all generated from this list — to add, remove or
    | change a question, edit this file only.
    |
    | Each field:
    |   key          Input name. `name`, `email` and `whatsapp` are stored in
    |                their own columns; every other key goes into `answers`.
    |   label        Shown above the input and as the admin column heading.
    |   type         text, email, tel, number, date, select or radio.
    |   options      For select/radio: a list of values, or value => label.
    |   placeholder  For text-like inputs, and the empty option of a select.
    |   rules        Laravel validation rules. select/radio fields are also
    |                limited to their options automatically.
    |   width        "half" or "full" (radio groups are always full width).
    |   in_table     Show as a column in Admin → Leads.
    |   table_label  Optional shorter column heading.
    |   table_width  Optional grid width of that column (default "1fr").
    |   default      Optional pre-selected option for a radio group.
    |
    */

    'fields' => [
        [
            'key' => 'name',
            'label' => 'Full name',
            'type' => 'text',
            'placeholder' => 'e.g. Ayesha Khan',
            'rules' => ['required', 'string', 'max:120'],
            'width' => 'half',
        ],
        [
            'key' => 'email',
            'label' => 'Email',
            'type' => 'email',
            'placeholder' => 'you@email.com',
            'rules' => ['required', 'string', 'email', 'max:190'],
            'width' => 'half',
        ],
        [
            'key' => 'whatsapp',
            'label' => 'WhatsApp number',
            'type' => 'tel',
            'placeholder' => '+92 3XX XXXXXXX',
            'rules' => ['required', 'string', 'min:7', 'max:30', 'regex:/^\+?[0-9 ()-]+$/'],
            'width' => 'half',
        ],
        [
            'key' => 'graduation_year',
            'label' => 'DPT graduation year',
            'type' => 'select',
            'placeholder' => 'Select year',
            'options' => ['2026', '2025', '2024', '2023 or earlier', 'Still studying'],
            'rules' => ['required'],
            'width' => 'half',
            'in_table' => true,
            'table_label' => 'Grad year',
            'table_width' => '0.7fr',
        ],
        [
            'key' => 'university',
            'label' => 'University',
            'type' => 'text',
            'placeholder' => 'Your DPT university',
            'rules' => ['required', 'string', 'max:150'],
            'width' => 'half',
            'in_table' => true,
            'table_width' => '1.3fr',
        ],
        [
            'key' => 'clinical_experience',
            'label' => 'Clinical experience',
            'type' => 'select',
            'placeholder' => 'Select',
            'options' => ['None yet', 'Less than 1 year', '1–3 years', '3+ years'],
            'rules' => ['required'],
            'width' => 'half',
        ],
        [
            'key' => 'fccpt',
            'label' => 'Have you started credential evaluation (FCCPT)?',
            'type' => 'radio',
            'options' => ['Not yet', 'In progress', 'Completed'],
            'rules' => ['required'],
            'in_table' => true,
            'table_label' => 'FCCPT',
            'table_width' => '0.9fr',
        ],
    ],

];
