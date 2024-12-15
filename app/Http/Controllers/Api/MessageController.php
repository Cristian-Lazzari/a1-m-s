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
        $data = $request->all();   
        Log::info("  quanlcuno ha inviato un messaggio : " , $data);
        
        $source = Source::where('domain' , $data['source'])->firstOrFail();
        if (!$source) {
            $source = new Source();
            $source->domain = $data['source'];
            $source->save();
        }
        $message = new Message();
        
        $message->wa_id = $data['wa_id'];
        $message->type = $data['type'];
        $message->source = $source->id;
        
        $message->save();
        
        return $data;
        Log::info("  sorgente dopo save : " , $source);
    }

}
