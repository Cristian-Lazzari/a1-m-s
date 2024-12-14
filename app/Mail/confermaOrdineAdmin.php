<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class confermaOrdineAdmin extends Mailable
{
    use Queueable, SerializesModels;
    public $content_mail;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($content_mail)
    {
        $this->content_mail = $content_mail;

    }


    public function build()
    {
        return $this->subject('Notifica da ' . config('configurazione.name'))
            ->view('emails.confermaOrderAdmin');
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
