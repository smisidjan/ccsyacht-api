<?php

namespace App\Mail;

use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Illuminate\Support\Facades\Log;

class MailtrapTransport extends AbstractTransport
{
    protected string $apiKey;

    public function __construct(string $apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    protected function doSend(SentMessage $message): void
    {
        try {
            Log::info('MailtrapTransport: Starting to send email');

            $email = MessageConverter::toEmail($message->getOriginalMessage());
            Log::info('MailtrapTransport: Email converted', [
                'subject' => $email->getSubject(),
                'to' => array_map(fn($a) => $a->getAddress(), $email->getTo()),
                'from' => array_map(fn($a) => $a->getAddress(), $email->getFrom())
            ]);

            $mailtrapEmail = new MailtrapEmail();

        // Set from address
        $from = $email->getFrom();
        if (!empty($from)) {
            $fromAddress = $from[0];
            $mailtrapEmail->from(new Address($fromAddress->getAddress(), $fromAddress->getName() ?? ''));
        }

        // Set to addresses
        foreach ($email->getTo() as $to) {
            $mailtrapEmail->to(new Address($to->getAddress(), $to->getName() ?? ''));
        }

        // Set CC if any
        foreach ($email->getCc() as $cc) {
            $mailtrapEmail->cc(new Address($cc->getAddress(), $cc->getName() ?? ''));
        }

        // Set BCC if any
        foreach ($email->getBcc() as $bcc) {
            $mailtrapEmail->bcc(new Address($bcc->getAddress(), $bcc->getName() ?? ''));
        }

        // Set subject
        $mailtrapEmail->subject($email->getSubject() ?? '');

        // Set body
        if ($email->getHtmlBody()) {
            $mailtrapEmail->html($email->getHtmlBody());
        }
        if ($email->getTextBody()) {
            $mailtrapEmail->text($email->getTextBody());
        }

        // Set reply-to if present
        $replyTo = $email->getReplyTo();
        if (!empty($replyTo)) {
            $replyToAddress = $replyTo[0];
            $mailtrapEmail->replyTo(new Address($replyToAddress->getAddress(), $replyToAddress->getName() ?? ''));
        }

        // Send via Mailtrap API
        Log::info('MailtrapTransport: Sending via Mailtrap API', [
            'api_key_length' => strlen($this->apiKey),
            'api_key_prefix' => substr($this->apiKey, 0, 8) . '...'
        ]);

        $client = MailtrapClient::initSendingEmails(apiKey: $this->apiKey);
        $response = $client->send($mailtrapEmail);

        Log::info('MailtrapTransport: Email sent successfully', [
            'response' => $response
        ]);

        } catch (\Exception $e) {
            Log::error('MailtrapTransport: Failed to send email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function __toString(): string
    {
        return 'mailtrap+api://send.api.mailtrap.io';
    }
}