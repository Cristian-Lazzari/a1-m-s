<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WaFailureAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $alert;

    public function __construct(array $alert)
    {
        $this->alert = $alert;
    }

    public function build()
    {
        $restaurant = $this->alert['restaurant']['name'] ?? 'A1MS';
        $flowLabel  = $this->alert['flow_label'] ?? 'Flusso';

        return $this->subject('[ALERT] ' . $flowLabel . ' fallito - ' . $restaurant)
            ->view('emails.wa-failure-alert');
    }
}
