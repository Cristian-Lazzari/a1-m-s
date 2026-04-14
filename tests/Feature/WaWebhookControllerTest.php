<?php

namespace Tests\Feature;

use App\Http\Controllers\Webhooks\WaController;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WaWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_handles_interactive_button_replies(): void
    {
        $sourceId = $this->createSource();
        $messageId = 'wamid.interactive.1';

        DB::table('messages')->insert([
            'wa_id' => $messageId,
            'source' => $sourceId,
            'response' => null,
            'type' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->bindWebhookControllerSpy(function (array $payload, Source $source) use ($messageId, $sourceId) {
            return $payload === [
                'wa_id' => $messageId,
                'number' => '393331112233',
                'response' => 1,
            ] && (int) $source->id === $sourceId;
        });

        $response = $this->postJson('/webhook/wa', [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '393331112233',
                                        'type' => 'interactive',
                                        'context' => [
                                            'id' => $messageId,
                                        ],
                                        'interactive' => [
                                            'button_reply' => [
                                                'id' => 'Conferma',
                                                'title' => 'Conferma',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertSeeText('EVENT_RECEIVED');
    }

    public function test_webhook_handles_template_fallback_replies_using_button_payload(): void
    {
        $sourceId = $this->createSource();
        $messageId = 'wamid.template.1';

        DB::table('messages')->insert([
            'wa_id' => $messageId,
            'source' => $sourceId,
            'response' => null,
            'type' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->bindWebhookControllerSpy(function (array $payload, Source $source) use ($messageId, $sourceId) {
            return $payload === [
                'wa_id' => $messageId,
                'number' => '393339998887',
                'response' => 1,
            ] && (int) $source->id === $sourceId;
        });

        $response = $this->postJson('/webhook/wa', [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '393339998887',
                                        'type' => 'button',
                                        'context' => [
                                            'id' => $messageId,
                                        ],
                                        'button' => [
                                            'payload' => 'Conferma',
                                            'text' => 'Apri dashboard',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertSeeText('EVENT_RECEIVED');
    }

    private function bindWebhookControllerSpy(callable $assertion): void
    {
        $controller = \Mockery::mock(WaController::class)->makePartial();
        $controller->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('handle_p2')
            ->once()
            ->withArgs(function (array $payload, Source $source) use ($assertion) {
                return $assertion($payload, $source);
            })
            ->andReturnNull();

        $this->app->instance(WaController::class, $controller);
    }

    private function createSource(): int
    {
        return DB::table('sources')->insertGetId([
            'db_name' => 'tenant_demo',
            'host' => '127.0.0.1',
            'username' => 'tenant_user',
            'token' => 'tenant_token',
            'app_url' => 'https://tenant.example.test',
            'app_domain' => 'tenant.example.test',
            'app_name' => 'Tenant Demo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
