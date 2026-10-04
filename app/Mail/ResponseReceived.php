<?php

namespace App\Mail;

use App\Models\BidResponse;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResponseReceived extends Mailable
{
    public function __construct(public BidResponse $response) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Response received: '.$this->response->bid->reference_number);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.response-received');
    }
}
