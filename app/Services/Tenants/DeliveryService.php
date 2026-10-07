<?php

namespace App\Services\Tenants;

use App\Models\Tenant;
use App\Models\WaDelivery;
use App\Services\WaFailureAlertService;
use Illuminate\Support\Facades\Log;

/**
 * Consegna gli eventi WhatsApp ai ristoranti.
 *
 * L'evento si salva prima di provarci: se il gestionale è giù o lento, il
 * comando wa:retry-deliveries lo riprova con attese crescenti. Così una
 * disdetta del cliente non si perde per un hosting che ha avuto un singhiozzo.
 */
class DeliveryService
{
    /** Minuti di attesa prima del tentativo N (dal secondo in poi). */
    private const BACKOFF_MINUTES = [1, 5, 15, 60, 180];

    public function __construct(private TenantClient $client)
    {
    }

    /** Salva l'evento senza ancora mandarlo: il webhook deve rispondere a Meta in fretta. */
    public function queue(Tenant $tenant, string $endpoint, array $payload): WaDelivery
    {
        return WaDelivery::create([
            'tenant_id' => $tenant->id,
            'endpoint' => $endpoint,
            'payload' => $payload,
            'status' => WaDelivery::STATUS_PENDING,
            'next_attempt_at' => now(),
        ]);
    }

    public function deliver(Tenant $tenant, string $endpoint, array $payload): WaDelivery
    {
        $delivery = $this->queue($tenant, $endpoint, $payload);
        $this->attempt($delivery);

        return $delivery;
    }

    /** @param int[] $ids */
    public function attemptMany(array $ids): void
    {
        WaDelivery::whereIn('id', $ids)
            ->where('status', WaDelivery::STATUS_PENDING)
            ->orderBy('id')
            ->get()
            ->each(fn (WaDelivery $delivery) => $this->attempt($delivery));
    }

    /** @return int quante consegne sono state ritentate */
    public function retryDue(int $limit = 50): int
    {
        $due = WaDelivery::where('status', WaDelivery::STATUS_PENDING)
            ->where('next_attempt_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($due as $delivery) {
            $this->attempt($delivery);
        }

        return $due->count();
    }

    public function attempt(WaDelivery $delivery): void
    {
        $delivery->attempts++;

        try {
            $tenant = $delivery->tenant;

            if (! $tenant || ! $tenant->active) {
                throw new \RuntimeException('Ristorante disattivato o eliminato.');
            }

            $response = $this->client->post($tenant, $delivery->endpoint, $delivery->payload);

            if (! $response->successful()) {
                throw new \RuntimeException('HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 300));
            }

            $delivery->fill(['status' => WaDelivery::STATUS_SENT, 'last_error' => null, 'next_attempt_at' => null])->save();
        } catch (\Throwable $e) {
            $this->failed($delivery, $e);
        }
    }

    private function failed(WaDelivery $delivery, \Throwable $e): void
    {
        $max = (int) config('whatsapp.delivery_max_attempts');
        $giveUp = $delivery->attempts >= $max;

        $delivery->fill([
            'status' => $giveUp ? WaDelivery::STATUS_FAILED : WaDelivery::STATUS_PENDING,
            'last_error' => mb_substr($e->getMessage(), 0, 1000),
            'next_attempt_at' => $giveUp ? null : now()->addMinutes(self::BACKOFF_MINUTES[$delivery->attempts - 1] ?? 180),
        ])->save();

        Log::warning('(WA Delivery) Consegna al ristorante non riuscita', [
            'delivery_id' => $delivery->id,
            'tenant_id' => $delivery->tenant_id,
            'endpoint' => $delivery->endpoint,
            'attempts' => $delivery->attempts,
            'error' => $e->getMessage(),
        ]);

        // Si avvisa una volta sola, quando si smette di riprovare.
        if ($giveUp) {
            app(WaFailureAlertService::class)->notify('wa_delivery_failed', [
                'tenant' => $delivery->tenant,
                'message' => "Evento {$delivery->endpoint} non consegnato dopo {$delivery->attempts} tentativi: " . $e->getMessage(),
                'resource' => ['delivery_id' => $delivery->id],
            ]);
        }
    }
}
