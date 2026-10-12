<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Whatsapp\ReminderTemplate;
use App\Services\Whatsapp\TemplateManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Il modello del promemoria del ristorante: stato delle versioni e proposta di
 * un testo nuovo. Il testo si scrive con le variabili per nome ({nome}, {ora}…).
 */
class TemplateController extends Controller
{
    /** GET /api/v1/templates */
    public function index(Request $request, TemplateManager $templates): JsonResponse
    {
        $account = $this->account($request);

        return response()->json([
            'connected' => $account !== null,
            'templates' => $account ? $templates->payload($account) : [],
            'variables' => ReminderTemplate::VARIABLES,
            'required' => ReminderTemplate::REQUIRED,
            'defaults' => ReminderTemplate::DEFAULTS,
            'limits' => ['body' => ReminderTemplate::MAX_BODY, 'button' => ReminderTemplate::MAX_BUTTON],
        ]);
    }

    /**
     * POST /api/v1/templates  { "language": "it", "body": "...", "buttons": ["Confermo", "Devo disdire"] }
     *
     * 422 con "errors" se il testo non passerebbe da Meta o Meta lo rifiuta subito.
     */
    public function store(Request $request, TemplateManager $templates): JsonResponse
    {
        $data = $request->validate([
            'language' => ['required', Rule::in(array_keys(ReminderTemplate::DEFAULTS))],
            'body' => ['required', 'string', 'max:2000'],
            'buttons' => ['required', 'array', 'size:2'],
            'buttons.*' => ['nullable', 'string', 'max:100'],
        ]);

        $account = $this->account($request);

        if (! $account) {
            return response()->json(['ok' => false, 'errors' => ['Il numero WhatsApp del locale non è collegato.']], 409);
        }

        $result = $templates->propose($account, $data['language'], $data['body'], $data['buttons']);

        return response()->json([
            'ok' => $result['ok'],
            'errors' => $result['errors'],
            'template' => $result['template']?->toTenantArray(),
            'templates' => $templates->payload($account),
        ], $result['ok'] ? 200 : 422);
    }

    private function account(Request $request)
    {
        return $request->attributes->get('tenant')->accounts()->where('status', 'active')->latest('id')->first();
    }
}
