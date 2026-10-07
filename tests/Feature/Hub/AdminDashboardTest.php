<?php

namespace Tests\Feature\Hub;

use App\Models\User;

class AdminDashboardTest extends HubTestCase
{
    public function test_dashboard_is_only_for_logged_in_admins(): void
    {
        $this->get('/admin')->assertRedirect('/login');

        [$tenant] = $this->tenant('Da Mario');
        $this->account($tenant);

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Da Mario')
            ->assertSee('+39 06 1234567')
            ->assertDontSee($tenant->private_token);
    }

    public function test_admin_create_command(): void
    {
        $this->artisan('admin:create', ['email' => 'cristian@future-plus.it'])
            ->expectsQuestion('Password (almeno 12 caratteri)', 'una-password-lunga')
            ->assertSuccessful();

        $this->post('/login', ['email' => 'cristian@future-plus.it', 'password' => 'una-password-lunga'])
            ->assertRedirect('/admin');
    }
}
