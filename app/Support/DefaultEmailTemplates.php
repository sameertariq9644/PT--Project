<?php

namespace App\Support;

use App\Models\EmailTemplate;

/**
 * The system email templates and their starting text. Admins edit the stored
 * copies under Admin → Email templates; these are only the defaults.
 */
class DefaultEmailTemplates
{
    /**
     * @return array<string, array{name: string, subject: string, body: string}>
     */
    public static function all(): array
    {
        return [
            EmailTemplate::LeadVideo => [
                'name' => 'Lead video',
                'subject' => 'Your US physical therapy pathway — the detailed video',
                'body' => <<<'MD'
                Hi {{name}},

                Thank you for completing the eligibility check. Based on your answers, the US pathway looks like a realistic option for you.

                I recorded a detailed walkthrough that explains every step — credential evaluation, TOEFL, the NPTE, licensure and visas — and how I work with students:

                [Watch the detailed video]({{video_url}})

                Take your time with it. When you are ready, you can choose a plan on the Services page of our website.

                Questions? Reply to this email or message us on WhatsApp: {{whatsapp}}

                Warm regards,
                {{site_name}}
                MD,
            ],
            EmailTemplate::OrderReceived => [
                'name' => 'Order received',
                'subject' => 'We received your payment receipt — order {{order_number}}',
                'body' => <<<'MD'
                Hi {{name}},

                Thank you. We have received your payment receipt for **{{plan}}** ({{amount}}).

                Your order number is **{{order_number}}**. We verify bank transfers manually, usually within one working day, and will email you as soon as it is confirmed.

                If you need anything in the meantime, message us on WhatsApp: {{whatsapp}}

                Warm regards,
                {{site_name}}
                MD,
            ],
            EmailTemplate::OrderConfirmed => [
                'name' => 'Order confirmed',
                'subject' => 'Payment confirmed — welcome to {{plan}}',
                'body' => <<<'MD'
                Hi {{name}},

                Good news — your payment for **{{plan}}** ({{amount}}) is confirmed. Order number: **{{order_number}}**.

                **Next step:** message us on WhatsApp at {{whatsapp}} with your order number so we can schedule your first session.

                I am looking forward to working with you.

                Warm regards,
                {{site_name}}
                MD,
            ],
            EmailTemplate::OrderRejected => [
                'name' => 'Order rejected',
                'subject' => 'We could not verify your payment — order {{order_number}}',
                'body' => <<<'MD'
                Hi {{name}},

                We were not able to verify the payment for order **{{order_number}}** ({{plan}}, {{amount}}).

                This usually means the receipt was unclear or the amount did not match. Please message us on WhatsApp at {{whatsapp}} or reply to this email and we will sort it out with you.

                Warm regards,
                {{site_name}}
                MD,
            ],
        ];
    }

    /**
     * The stored system template for a key, created from the defaults if missing.
     */
    public static function resolve(string $key): EmailTemplate
    {
        $template = EmailTemplate::findByKey($key);

        if ($template !== null) {
            return $template;
        }

        $template = new EmailTemplate(static::all()[$key]);
        $template->key = $key;
        $template->is_system = true;
        $template->save();

        return $template;
    }
}
