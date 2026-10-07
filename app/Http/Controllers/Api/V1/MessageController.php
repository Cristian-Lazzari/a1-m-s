<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WaMessage;
use App\Services\Whatsapp\MessageSender;
use App\Services\Whatsapp\MessageSenderException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    /**
     * POST /api/v1/messages
     *
     * {
     *   "channel": "platform" | "restaurant",
     *   "to": "393331112233",
     *   "message":  { "type": "interactive", "interactive": {...} },   // libero, se la finestra è aperta
     *   "fallback": { "name": "...", "language": {"code": "it"}, "components": [...] }, // template
     *   "ref": "res:123",
     *   "context_message_id": "wamid..."
     * }
     */
    public function store(Request $request, MessageSender $sender): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in([WaMessage::CHANNEL_PLATFORM, WaMessage::CHANNEL_RESTAURANT])],
            'to' => ['required', 'string', 'regex:/^\+?[0-9 ]{8,20}$/'],
            'message' => ['nullable', 'array', 'required_without:fallback'],
            'message.type' => ['required_with:message', Rule::in(['text', 'interactive'])],
            'fallback' => ['nullable', 'array', 'required_without:message'],
            'fallback.name' => ['required_with:fallback', 'string', 'max:512'],
            'fallback.language.code' => ['required_with:fallback', 'string', 'max:15'],
            'fallback.components' => ['nullable', 'array'],
            'ref' => ['nullable', 'string', 'max:64'],
            'context_message_id' => ['nullable', 'string', 'max:128'],
        ]);

        try {
            $result = $sender->send(
                $request->attributes->get('tenant'),
                $data['channel'],
                $data['to'],
                $request->input('message'),
                $request->input('fallback'),
                $data['ref'] ?? null,
                $data['context_message_id'] ?? null,
            );
        } catch (MessageSenderException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 409);
        }

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
