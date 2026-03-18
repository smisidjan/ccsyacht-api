<?php

namespace App\Mail;

use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

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
        $email = MessageConverter::toEmail($message->getOriginalMessage());

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
        $client = MailtrapClient::initSendingEmails(apiKey: $this->apiKey);
        $client->send($mailtrapEmail);
    }

    public function __toString(): string
    {
        return 'mailtrap+api://send.api.mailtrap.io';
    }
}