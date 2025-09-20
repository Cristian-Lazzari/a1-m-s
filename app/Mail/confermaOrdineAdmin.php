<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class confermaOrdineAdmin extends Mailable
{
    use Queueable, SerializesModels;
        public $bodymail;
        public $fromAddress;
        public $fromName;

  

    public function __construct($bodymail, $fromAddress, $fromName = null)
    {
        $this->bodymail = $bodymail;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName ?? 'Default Sender';
    }

    public function build()
    {
        return $this->from($this->fromAddress, $this->fromName)
            ->subject('Conferma Ordine')
            ->view('emails.confermaOrderAdmin')
            ->with(['bodymail' => $this->bodymail]);
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
