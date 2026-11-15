<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileCustomerAppTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => Str::lower(Str::random(10)).'@example.com',
            'password' => Hash::make('password'), 'role' => $role, 'branch_id' => null,
            'email_verified_at' => now(),
        ]);
    }

    public function test_customer_screens_respond(): void
    {
        Sanctum::actingAs($this->user('customer'));

        $this->getJson('/api/mobile/customer/home')->assertOk()
            ->assertJsonStructure(['profile', 'counts' => ['all_orders', 'unread_notifications'], 'recent_orders']);
        foreach (['products', 'orders', 'payments', 'notifications', 'profile', 'history'] as $path) {
            $this->getJson("/api/mobile/customer/{$path}")->assertOk()->assertJsonStructure(['data']);
        }
        $this->getJson('/api/mobile/customer/notifications/unread-count')->assertOk()->assertJsonStructure(['count']);
        $this->getJson('/api/mobile/customer/chat')->assertOk();
    }

    public function test_customer_can_update_profile_and_chat(): void
    {
        Sanctum::actingAs($this->user('customer'));

        $this->postJson('/api/mobile/customer/profile', [
            'name' => 'Jane Buyer', 'phone' => '0700000000', 'address' => 'Kampala Road',
        ])->assertOk()->assertJsonPath('data.name', 'Jane Buyer');

        $this->postJson('/api/mobile/customer/chat/messages', ['message' => 'Hello'])->assertSuccessful();
        $this->postJson('/api/mobile/customer/notifications/read-all')->assertOk();
    }

    public function test_customer_cannot_open_someone_elses_order(): void
    {
        Sanctum::actingAs($this->user('customer'));

        $this->getJson('/api/mobile/customer/orders/999999')->assertNotFound();
    }

    public function test_staff_can_use_the_notification_inbox(): void
    {
        Sanctum::actingAs($this->user('cashier'));

        $this->getJson('/api/mobile/customer/notifications/unread-count')->assertOk();
        $this->getJson('/api/mobile/customer/notifications')->assertOk();
    }

    public function test_staff_cannot_use_customer_only_endpoints(): void
    {
        Sanctum::actingAs($this->user('cashier'));

        $this->getJson('/api/mobile/customer/home')->assertForbidden();
        $this->postJson('/api/mobile/customer/profile', ['name' => 'X'])->assertForbidden();
        $this->postJson('/api/mobile/customer/chat/messages', ['message' => 'Hi'])->assertForbidden();
    }

    public function test_guests_are_rejected(): void
    {
        $this->getJson('/api/mobile/customer/home')->assertUnauthorized();
    }
}
