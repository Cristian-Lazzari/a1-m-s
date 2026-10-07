<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Whatsapp\OnboardingLinks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /** GET /api/v1/account — stato del numero WhatsApp del ristorante. */
    public function show(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $account = $tenant->accounts()->latest('id')->first();

        return response()->json([
            'connected' => $account?->status === 'active',
            'status' => $account?->status,
            'display_phone' => $account?->display_phone,
            'verified_name' => $account?->verified_name,
            'last_error' => $account?->status === 'error' ? $account->last_error : null,
        ]);
    }

    /**
     * POST /api/v1/onboarding-links — link monouso (30 minuti) alla pagina di
     * collegamento del numero. Il gestionale ci manda il ristoratore.
     */
    public function onboardingLink(Request $request, OnboardingLinks $links): JsonResponse
    {
        $data = $request->validate(['return_url' => ['nullable', 'url', 'max:500']]);

        return response()->json([
            'url' => $links->create($request->attributes->get('tenant'), $data['return_url'] ?? null),
            'expires_in' => OnboardingLinks::TTL_MINUTES * 60,
        ]);
    }
}
