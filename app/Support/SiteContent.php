<?php

namespace App\Support;

/**
 * Copy for the public pages, taken from the design mockups. Bracketed values
 * are placeholders the client still has to supply.
 */
class SiteContent
{
    /**
     * The six pathway steps, used by the home page cards and the Pathway page.
     *
     * @return list<array<string, mixed>>
     */
    public static function steps(): array
    {
        return [
            [
                'tag' => 'Documents',
                'short' => 'Evaluation',
                'title' => 'Credential evaluation',
                'summary' => 'Your DPT coursework is reviewed against US standards by an approved agency such as FCCPT. We prepare your file so gaps don’t surprise you.',
                'body' => 'An approved agency such as FCCPT compares your DPT coursework with US physical therapy education standards. Any gaps found may need extra coursework before you can move on.',
                'time' => '[X–Y months]',
                'needs' => [
                    'Degree and official transcripts',
                    'Course descriptions / syllabus from your university',
                    'Proof of registration or licence in Pakistan, if any',
                ],
                'stuck' => 'incomplete course descriptions and documents sent directly by the student instead of the university.',
            ],
            [
                'tag' => 'English',
                'short' => 'TOEFL',
                'title' => 'TOEFL iBT',
                'summary' => 'Most states and credentialing bodies need TOEFL scores. We set your target by section and a timeline that fits your exam plan.',
                'body' => 'Most states and credentialing bodies require TOEFL iBT scores, often with minimum scores for each section — speaking is the one most people underestimate.',
                'time' => '[X–Y months]',
                'needs' => [
                    'Your target state’s minimum section scores',
                    'A study plan focused on speaking and writing',
                    'Scores sent directly to the right agency',
                ],
                'stuck' => 'booking the exam before knowing the exact section minimums for their chosen state.',
            ],
            [
                'tag' => 'Exam',
                'short' => 'NPTE',
                'title' => 'The NPTE',
                'summary' => 'The National Physical Therapy Exam by FSBPT. We build a study strategy, pick the right prep resources and time your attempt.',
                'body' => 'The National Physical Therapy Examination, run by FSBPT, is the licensing exam every US physical therapist must pass. You need approval to test from a state board first.',
                'time' => '[X–Y months]',
                'needs' => [
                    'Authorization to Test from your state board',
                    'A structured study plan and practice exams',
                    'A realistic exam date',
                ],
                'stuck' => 'attempts are limited, so testing before you’re ready is expensive in both money and time.',
            ],
            [
                'tag' => 'Licence',
                'short' => 'State licence',
                'title' => 'State licensure',
                'summary' => 'Every state board has its own rules and jurisprudence exam. Choosing the right state first can save you months.',
                'body' => 'Each state board sets its own rules for internationally educated PTs — including jurisprudence exams and, in some states, a period of supervised clinical practice.',
                'time' => '[X–Y months]',
                'needs' => [
                    'A carefully chosen first state',
                    'Board application and fees',
                    'Jurisprudence exam, where required',
                ],
                'stuck' => 'picking a state for its job market without checking its rules for foreign-educated PTs.',
            ],
            [
                'tag' => 'Immigration',
                'short' => 'Visa',
                'title' => 'VisaScreen & visa route',
                'summary' => 'The Healthcare Worker Certificate, then the visa route that fits you — such as H-1B or an employment-based green card.',
                'body' => 'Healthcare workers need a certificate (such as VisaScreen or the FCCPT certificate) before a visa can be issued. Then comes the route that fits you — for example an H-1B or an employment-based green card.',
                'time' => '[Varies]',
                'needs' => [
                    'Healthcare Worker Certificate',
                    'A sponsoring employer for most routes',
                    'An immigration attorney for the filing itself',
                ],
                'stuck' => 'relying on agents who promise guaranteed visas. No one can guarantee one.',
            ],
            [
                'tag' => 'Arrival',
                'short' => 'Arrival',
                'title' => 'Job offer & relocation',
                'summary' => 'Finding sponsoring employers, interviewing well, and landing ready to practise — from someone who has done it.',
                'body' => 'Finding employers who sponsor international PTs, presenting your experience well, and landing ready to practise from day one.',
                'time' => '[X–Y months]',
                'needs' => [
                    'A US-style CV',
                    'Interview practice',
                    'A relocation and first-90-days plan',
                ],
                'stuck' => 'underselling Pakistani clinical experience in interviews.',
            ],
        ];
    }

    /**
     * The three service plans, used by the home page teaser and the Services page.
     *
     * @return list<array<string, mixed>>
     */
    public static function plans(): array
    {
        return [
            [
                'kicker' => 'Starting out',
                'name' => 'Roadmap Session',
                'teaser' => 'A 1:1 call to assess your profile and leave with a written, personalised step-by-step plan.',
                'description' => 'A focused 1:1 call to assess your profile and leave with a clear, written plan.',
                'price' => '$[—]',
                'featured' => false,
                'highlights' => ['60-minute video call', 'Personal roadmap document', 'State shortlist'],
                'features' => [
                    '60-minute video call',
                    'Profile & document assessment',
                    'Personal step-by-step roadmap (PDF)',
                    'Recommended first state shortlist',
                    '7 days of WhatsApp follow-up',
                ],
            ],
            [
                'kicker' => 'Credentialing & exams',
                'name' => 'Pathway Mentorship',
                'teaser' => 'Guided support through FCCPT evaluation, TOEFL and NPTE preparation, with regular check-ins.',
                'description' => 'Guided support through credential evaluation, TOEFL and the NPTE with regular check-ins.',
                'price' => '$[—]',
                'featured' => true,
                'highlights' => ['Everything in Roadmap', 'Document review & submissions', 'NPTE study plan & check-ins'],
                'features' => [
                    'Everything in Roadmap Session',
                    'Credential evaluation file review',
                    'TOEFL target & study plan',
                    'NPTE study strategy & resources',
                    '[2] check-in calls per month',
                    'WhatsApp support for [X] months',
                ],
            ],
            [
                'kicker' => 'Start to finish',
                'name' => 'Complete Journey',
                'teaser' => 'End-to-end guidance from evaluation to licensure, visa process, job search and relocation.',
                'description' => 'End-to-end guidance from evaluation to licence, visa process, job search and relocation.',
                'price' => '$[—]',
                'featured' => false,
                'highlights' => ['Everything in Mentorship', 'Licensure & visa guidance', 'CV, interviews & employers'],
                'features' => [
                    'Everything in Pathway Mentorship',
                    'State licensure guidance',
                    'VisaScreen & visa route planning',
                    'US CV, interview prep & employer list',
                    'Relocation & first-90-days plan',
                    'Priority support until you land',
                ],
            ],
        ];
    }

    /**
     * Plan comparison rows: true is a tick, false is a dash, a string is shown as text.
     * Columns are Roadmap, Mentorship, Complete.
     *
     * @return array<string, array{0: bool|string, 1: bool|string, 2: bool|string}>
     */
    public static function comparison(): array
    {
        return [
            '1:1 video calls' => ['1', '[X]', 'Unlimited*'],
            'Personal roadmap document' => [true, true, true],
            'Credential evaluation file review' => [false, true, true],
            'TOEFL & NPTE study strategy' => [false, true, true],
            'State licensure guidance' => [false, false, true],
            'Visa route planning' => [false, false, true],
            'CV, interviews & employers' => [false, false, true],
            'WhatsApp support' => ['7 days', '[X] months', 'Until you land'],
        ];
    }

    /**
     * @return list<array{category: string, question: string, answer: string}>
     */
    public static function faqs(): array
    {
        return [
            ['category' => 'General', 'question' => 'Who is this consultation for?', 'answer' => 'Pakistani DPT graduates (and final-year students) who want to build a physical therapy career in the United States and want honest, personal guidance on how to get there.'],
            ['category' => 'General', 'question' => 'Is my DPT from Pakistan accepted in the US?', 'answer' => 'Your degree must be evaluated against US standards by an approved agency such as FCCPT. Many Pakistani DPT graduates complete this successfully, sometimes after extra coursework. We look at your transcripts and tell you what to expect.'],
            ['category' => 'General', 'question' => 'Can you guarantee a visa or a job?', 'answer' => 'No — and you should be careful of anyone who does. We guide you through every step and help you avoid costly mistakes, but exams, visas and job offers depend on you, employers and US authorities.'],
            ['category' => 'Process', 'question' => 'How long does the whole process take?', 'answer' => '[Typical range] — it depends on credential evaluation, how quickly you pass TOEFL and the NPTE, your state and your visa route. Your roadmap gives you a personal timeline.'],
            ['category' => 'Process', 'question' => 'What happens after I fill the eligibility form?', 'answer' => 'We review your answers. If you’re a fit, you receive an email with a detailed recorded video explaining the process and our services. There is no obligation to buy anything.'],
            ['category' => 'Payments', 'question' => 'How do I pay?', 'answer' => 'Choose a plan, enter your details, and transfer the amount to the bank account shown at checkout. Then upload a screenshot of your receipt. We verify it and confirm by email and WhatsApp.'],
            ['category' => 'Payments', 'question' => 'How long does payment verification take?', 'answer' => 'Usually within [24 hours] on working days. You’ll get a confirmation with your onboarding details once it’s verified.'],
            ['category' => 'Payments', 'question' => 'What is your refund policy?', 'answer' => '[Refund policy to be confirmed by the client.]'],
            ['category' => 'Sessions', 'question' => 'Are sessions in English or Urdu?', 'answer' => 'Both. Sessions happen over video call in whichever language you’re most comfortable with.'],
            ['category' => 'Sessions', 'question' => 'What time are sessions held?', 'answer' => 'Sessions are scheduled at times that work for Pakistan time (PKT). [Edit to match availability.]'],
        ];
    }

    /**
     * The five questions shown on the home page.
     *
     * @return list<array{question: string, answer: string}>
     */
    public static function homeFaqs(): array
    {
        $answers = array_column(static::faqs(), 'answer', 'question');

        return [
            ['question' => 'How long does the whole process take?', 'answer' => '[Typical range with a short explanation — depends on credential evaluation, exam attempts and the visa route.]'],
            ['question' => 'Is my DPT from Pakistan accepted in the US?', 'answer' => $answers['Is my DPT from Pakistan accepted in the US?']],
            ['question' => 'Can you guarantee a visa or a job?', 'answer' => $answers['Can you guarantee a visa or a job?']],
            ['question' => 'How do I pay, and when does my plan start?', 'answer' => $answers['How do I pay?']],
            ['question' => 'Are sessions in English or Urdu?', 'answer' => $answers['Are sessions in English or Urdu?']],
        ];
    }
}
