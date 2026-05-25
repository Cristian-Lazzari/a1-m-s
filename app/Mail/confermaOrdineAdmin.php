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
    public $lang;

    public function __construct($content_mail, $fromAddress, $fromName = null, $lang = 'it')
    {
        $this->content_mail = $content_mail;
        $this->fromAddress  = $fromAddress;
        $this->fromName     = $fromName ?? 'Default Sender';
        $this->lang         = $lang;
    }

    public function build()
    {
        // Imposta la locale per tradurre correttamente le stringhe __() nel template
        $this->locale($this->lang);

        // Il title nel bodymail è già tradotto e corretto (accepted o cancelled)
        $subject = $this->content_mail['title'] ?? __('admin.controllers.orders.accepted_title');

        $mail = $this->subject($subject)
            ->view('emails.confermaOrderAdmin')
            ->with(['content_mail' => $this->content_mail]);

        // Imposta il mittente solo se l'indirizzo è valido (evita crash con null)
        if (!empty($this->fromAddress)) {
            $mail->from($this->fromAddress, $this->fromName ?? '');
        }

        return $mail;
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments(): array
    {
        return [];
    }
}
