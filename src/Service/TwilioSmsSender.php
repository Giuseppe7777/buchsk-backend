<?php

namespace App\Service;

use Twilio\Rest\Client;

final class TwilioSmsSender implements SmsSender
{
    private Client $client;
    private string $from;

    public function __construct(string $sid, string $token, string $from)
    {
        $this->client = new Client($sid, $token);
        $this->from = $from;
    }

    public function send(string $to, string $message): void
    {
        $this->client->messages->create(
            $to,
            [
                'from' => $this->from,
                'body' => $message,
            ]
        );
    }
}
