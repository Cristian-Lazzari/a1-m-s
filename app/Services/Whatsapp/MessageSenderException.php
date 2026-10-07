<?php

namespace App\Services\Whatsapp;

use RuntimeException;

/** Invio impossibile per configurazione, non per un rifiuto di Meta. */
class MessageSenderException extends RuntimeException
{
}
