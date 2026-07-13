<?php

namespace App\Mail\Office;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPwMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $subject;

    public $assign;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $assign)
    {
        $this->subject = $subject;
        $this->assign = $assign['assign'];
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content()
    {
        return new Content(
            view: 'office/auth/forgot/pw/notice',
            with: ['assign' => $this->assign],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
