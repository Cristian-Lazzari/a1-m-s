<?php

namespace App\Http\Controllers\Api;

use App\Models\Source;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class MessageController extends Controller
{
    public function getNewMex(Request $request)
    {
        try {
            // Validazione dei dati in ingresso
            $validatedData = $request->validate([
                'source' => 'required|string|max:255',
                'wa_id' => 'required|string|max:255',
                'type' => 'required|string|max:50'
            ]);
    
            // Log iniziale
            Log::info("Qualcuno ha inviato un messaggio:", $validatedData);
    
            // Controlla se esiste la sorgente, altrimenti la crea
            $source = Source::firstOrCreate(
                ['domain' => $validatedData['source']],
                ['domain' => $validatedData['source']] // Valori da inserire se non esiste
            );
    
            // Creazione del nuovo messaggio
            $message = new Message();
            $message->wa_id = $validatedData['wa_id'];
            $message->type = $validatedData['type'];
            $message->source = $source->id;
            $message->save();
    
            // Log della sorgente dopo il salvataggio
            Log::info("Sorgente dopo il salvataggio:", $source->toArray());
    
            // Ritorna i dati ricevuti
            return response()->json(['success' => true, 'data' => $validatedData], 200);
        } catch (ValidationException $e) {
            // Gestione degli errori di validazione
            Log::error("Errore di validazione:", $e->errors());
            return response()->json(['success' => false, 'error' => $e->errors()], 422);
        } catch (Exception $e) {
            // Gestione generica degli errori
            Log::error("Errore nel salvataggio del messaggio:", ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Si è verificato un errore. Riprova più tardi.'], 500);
        }
    }

}
