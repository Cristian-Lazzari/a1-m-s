<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class confermaOrdineAdmin extends Mailable
{
    use Queueable, SerializesModels;
        public $content_mail;
        public $fromAddress;
        public $fromName;

  

    public function __construct($content_mail, $fromAddress, $fromName = null)
    {
        $this->content_mail = $content_mail;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName ?? 'Default Sender';
    }

    public function build()
    {
        return $this->from($this->fromAddress, $this->fromName)
            ->subject('Conferma Ordine')
            ->view('emails.confermaOrderAdmin')
            ->with(['content_mail' => $this->content_mail]);
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
