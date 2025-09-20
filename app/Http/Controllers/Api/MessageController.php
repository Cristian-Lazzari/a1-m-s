<?php

namespace App\Http\Controllers\Api;

use App\Models\Source;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class MessageController extends Controller
{
    // public function getNewMex(Request $request)
    // {
        
    //     //return response()->json(['success' => true, 'data' => $validatedData], 200);
        
    //     Log::info("Richiesta ricevuta:", $request->all());

    //     // Validazione dei dati in ingresso
    //     //$validatedData = $request->all();
    //     $validatedData = $request->validate([
    //         'source' => 'required',
    //         'wa_id'  => 'required',
    //         'type_1' => 'required',
    //         'type_2' => 'required',
    //     ]);

    //     Log::info("Dati validati correttamente:", $validatedData);

    //     // Controlla se esiste la sorgente, altrimenti la crea
    //     $source = Source::firstOrCreate(
    //         ['domain' => $validatedData['source']],
    //         ['domain' => $validatedData['source']]
    //     );

    //     // Decodifica wa_id e verifica se è valido
    //     $mex = json_decode($validatedData['wa_id'], true);
    //     if (!is_array($mex)) {
    //         return response()->json(['success' => false, 'error' => 'Si è verificato un errore. Riprova più tardi.'], 500);
    //     }
    //     //dd('ciao');

    //     Log::info("wa_id decodificato con successo:", ['wa_id' => $mex]);

    //     $i = 1;
    //     foreach ($mex as $id) {
    //         $message = new Message();
    //         $message->wa_id = $id;
    //         $message->type = $i == 1 ? $validatedData['type_1'] : $validatedData['type_2'];
    //         $message->source = $source->id;
    //         $message->save();
    //         $i++;
    //     }

    //     Log::info("Messaggi salvati con successo.");
    //     //$return response()->json(['success' => true, 'data' => 'diomerda'], 200);
    //     // Ritorna i dati ricevuti
       

    //     // } catch (ValidationException $e) {
    //     //     Log::error("Errore di validazione:", $e->errors());
    //     //     return response()->json(['success' => false, 'error' => $e->errors()], 422);
    //     // } catch (Exception $e) {
    //     //     Log::error("Errore nel salvataggio del messaggio:", [
    //     //         'message' => $e->getMessage(),
    //     //         'trace' => $e->getTraceAsString(),
    //     //     ]);
    //     //     return response()->json(['success' => false, 'error' => 'Si è verificato un errore. Riprova più tardi.'], 500);
    //     // }
    // }
    

}
