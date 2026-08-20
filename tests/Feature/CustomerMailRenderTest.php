<?php

namespace Tests\Feature;

use App\Mail\confermaOrdineAdmin;
use Tests\TestCase;

/**
 * La mail di conferma o annullamento è l'unica risposta che il cliente riceve
 * dopo che il ristoratore ha premuto il pulsante su WhatsApp. Questi test
 * tengono il template in piedi anche quando i dati del ristorante sono
 * incompleti: un template che esplode è una mail che non parte.
 */
class CustomerMailRenderTest extends TestCase
{
    public function test_prenotazione_con_impostazioni_incomplete(): void
    {
        // Il setting 'advanced' del ristorante non ha né 'dt' né i nomi sala.
        $html = $this->render($this->reservationBody(['property_adv' => []]));

        $this->assertStringContainsString('Prenotazione confermata', $html);
    }

    public function test_prenotazione_con_doppia_sala(): void
    {
        $html = $this->render($this->reservationBody([
            'property_adv' => ['dt' => '1', 'sala_1' => 'Sala interna', 'sala_2' => 'Dehors'],
            'sala' => 1,
        ]));

        $this->assertStringContainsString('Sala interna', $html);
    }

    public function test_prenotazione_con_ospiti_in_json(): void
    {
        $html = $this->render($this->reservationBody([
            'n_person' => json_encode(['adult' => 2, 'child' => 1]),
        ]));

        $this->assertNotEmpty($html);
    }

    public function test_prenotazione_con_data_in_formato_non_previsto(): void
    {
        $html = $this->render($this->reservationBody(['date_slot' => '21/08/2026 20:30 - 22:30']));

        $this->assertStringContainsString('21/08/2026 20:30 - 22:30', $html);
    }

    public function test_ordine_da_asporto(): void
    {
        $html = $this->render($this->orderBody());

        $this->assertNotEmpty($html);
    }

    public function test_ordine_a_domicilio(): void
    {
        $html = $this->render($this->orderBody([
            'comune' => 'Genova',
            'address' => 'Via Roma',
            'address_n' => '10',
            'delivery_cost' => 3.5,
        ]));

        $this->assertStringContainsString('Via Roma', $html);
    }

    private function render(array $bodymail): string
    {
        return (new confermaOrdineAdmin($bodymail, 'info@ristorante.test', 'Ristorante Demo', 'it'))->render();
    }

    private function reservationBody(array $override = []): array
    {
        return array_merge([
            'type' => 'res',
            'to' => 'user',
            'lang' => 'it',
            'title' => 'Prenotazione confermata',
            'subtitle' => '',
            'res_id' => 12,
            'name' => 'Mario',
            'surname' => 'Rossi',
            'email' => 'mario@example.test',
            'date_slot' => '21/08/2026 20:30',
            'message' => 'Tavolo vicino alla finestra',
            'sala' => 1,
            'phone' => '3331112233',
            'admin_phone' => '0102030405',
            'app_domain' => 'https://ristorante.test',
            'app_url' => 'https://ristorante.test',
            'app_name' => 'Ristorante Demo',
            'whatsapp_message_id' => 'wamid.res.1',
            'n_person' => 4,
            'status' => 1,
            'property_adv' => ['dt' => '0'],
        ], $override);
    }

    private function orderBody(array $override = []): array
    {
        return array_merge([
            'type' => 'or',
            'to' => 'user',
            'lang' => 'it',
            'title' => 'Ordine confermato',
            'subtitle' => '',
            'whatsapp_message_id' => 'wamid.or.1',
            'order_id' => 42,
            'name' => 'Mario',
            'surname' => 'Rossi',
            'email' => 'mario@example.test',
            'date_slot' => '21/08/2026 20:30',
            'message' => '',
            'phone' => '3331112233',
            'admin_phone' => '0102030405',
            'delivery_cost' => null,
            'comune' => null,
            'address' => null,
            'address_n' => null,
            'app_domain' => 'https://ristorante.test',
            'app_url' => 'https://ristorante.test',
            'app_name' => 'Ristorante Demo',
            'status' => 1,
            'cart' => ['products' => [], 'menus' => []],
            'total_price' => 42.5,
        ], $override);
    }
}
