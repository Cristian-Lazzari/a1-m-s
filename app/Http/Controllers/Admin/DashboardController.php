<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\WaDelivery;
use Illuminate\View\View;

/**
 * Colpo d'occhio sull'hub: ristoranti, numeri collegati, eventi bloccati.
 * Solo lettura: ristoranti e chiavi si gestiscono da riga di comando.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'tenants' => Tenant::with(['accounts' => fn ($q) => $q->latest('id')])
                ->withCount([
                    'deliveries as pending_count' => fn ($q) => $q->where('status', WaDelivery::STATUS_PENDING),
                    'deliveries as failed_count' => fn ($q) => $q->where('status', WaDelivery::STATUS_FAILED),
                ])
                ->orderBy('name')
                ->get(),
            'failed' => WaDelivery::with('tenant')
                ->where('status', WaDelivery::STATUS_FAILED)
                ->latest('id')
                ->limit(20)
                ->get(),
            'platformConfigured' => filled(config('whatsapp.platform.phone_number_id')) && filled(config('whatsapp.platform.token')),
            'metaConfigured' => filled(config('whatsapp.app_id')) && filled(config('whatsapp.app_secret')) && filled(config('whatsapp.embedded_signup_config_id')),
        ]);
    }
}
