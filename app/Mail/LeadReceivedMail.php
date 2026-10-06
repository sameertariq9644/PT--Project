<?php

namespace App\Mail;

use App\Models\Lead;
use App\Support\LeadForm;
use Illuminate\Mail\Mailables\Address;

class LeadReceivedMail extends AdminNotificationMail
{
    public function __construct(public Lead $lead)
    {
        parent::__construct();
    }

    protected function subjectLine(): string
    {
        return 'New lead: '.$this->lead->name;
    }

    protected function heading(): string
    {
        return 'New eligibility form submission';
    }

    protected function intro(): ?string
    {
        return 'Reply to this email to answer '.$this->lead->name.' directly.';
    }

    protected function rows(): array
    {
        $rows = [
            'Name' => $this->lead->name,
            'Email' => $this->lead->email,
            'WhatsApp' => $this->lead->whatsapp,
        ];

        foreach (LeadForm::answerFields() as $field) {
            $rows[$field['table_label']] = $this->lead->answer($field['key']);
        }

        $rows['Campaign'] = $this->lead->utm_campaign;

        return $rows;
    }

    protected function replyToAddress(): Address
    {
        return new Address($this->lead->email, $this->lead->name);
    }

    protected function action(): ?array
    {
        return ['url' => route('admin.leads.show', $this->lead), 'label' => 'Open lead in admin'];
    }
}
