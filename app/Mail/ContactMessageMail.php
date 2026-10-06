<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailables\Address;

class ContactMessageMail extends AdminNotificationMail
{
    public function __construct(public ContactMessage $contactMessage)
    {
        parent::__construct();
    }

    protected function subjectLine(): string
    {
        return 'Contact form: '.$this->contactMessage->topic.' — '.$this->contactMessage->name;
    }

    protected function heading(): string
    {
        return 'New message from the contact form';
    }

    protected function intro(): ?string
    {
        return 'Reply to this email to answer '.$this->contactMessage->name.' directly.';
    }

    protected function rows(): array
    {
        return [
            'Name' => $this->contactMessage->name,
            'Email' => $this->contactMessage->email,
            'WhatsApp' => $this->contactMessage->whatsapp,
            'Topic' => $this->contactMessage->topic,
            'Message' => $this->contactMessage->message,
        ];
    }

    protected function replyToAddress(): Address
    {
        return new Address($this->contactMessage->email, $this->contactMessage->name);
    }
}
