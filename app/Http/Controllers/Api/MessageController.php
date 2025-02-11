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
                'source' => 'required',
                'wa_id'  => 'required',
                'type_1' => 'required',
                'type_2' => 'required',
            ]);
    
            // Log iniziale
            Log::info("Messaggio redistrato dal be:", $validatedData);
    
            // Controlla se esiste la sorgente, altrimenti la crea
            $source = Source::firstOrCreate(
                ['domain' => $validatedData['source']],
                ['domain' => $validatedData['source']] // Valori da inserire se non esiste
            );
            $mex = json_decode($validatedData['wa_id'], true);
            $i = 1;
            
            foreach ($mex as $id) {
                // Creazione del nuovo messaggio
                $message = new Message();
                $message->wa_id = $id;
                $message->type = $i == 1 ? $validatedData['type_1'] : $validatedData['type_2'];
                
                $message->source = $source->id;
                $message->save();
                $i ++;
            } 
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
