<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class WebProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'manager'): User
    {
        $branch = Branch::query()->first() ?? Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);

        return User::create([
            'name' => 'Jane Akello', 'email' => Str::lower(Str::random(8)).'@example.com',
            'password' => Hash::make('OldPass123'), 'role' => $role, 'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_header_links_to_the_profile(): void
    {
        $this->actingAs($this->user())->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('href="'.route('profile.edit').'"', false)
            ->assertSee('aria-label="My profile"', false);
    }

    public function test_staff_see_their_details_without_customer_fields(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('My profile')
            ->assertSee($user->email)
            ->assertSee('Main Branch')
            ->assertSee('name="name"', false)
            ->assertDontSee('name="phone"', false)
            ->assertSee(route('signature.edit'), false);
    }

    public function test_staff_can_change_their_name(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Jane Nakato'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $this->assertSame('Jane Nakato', $user->fresh()->name);
    }

    public function test_a_blank_name_is_rejected(): void
    {
        $this->actingAs($this->user())->from(route('profile.edit'))
            ->put(route('profile.update'), ['name' => ''])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrorsIn('details', 'name');
    }

    public function test_photo_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');
        $user = $this->user();

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'photo' => UploadedFile::fake()->image('me.jpg', 200, 200),
        ])->assertSessionHas('success');

        $path = $user->fresh()->profile_pic;
        $this->assertStringStartsWith('avatars/', $path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)->put(route('profile.update'), ['name' => $user->name, 'remove_photo' => '1']);

        $this->assertNull($user->fresh()->profile_pic);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_customers_can_update_phone_and_address(): void
    {
        $user = $this->user('customer');
        Customer::create(['user_id' => $user->id, 'branch_id' => $user->branch_id, 'name' => $user->name, 'email' => $user->email, 'phone' => '0700000000']);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertSee('name="phone"', false)
            ->assertSee('0700000000')
            ->assertDontSee(route('signature.edit'), false);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Jane Nakato', 'phone' => '0772123456', 'address' => 'Plot 4, Ntinda',
        ])->assertSessionHas('success');

        $customer = Customer::where('user_id', $user->id)->first();
        $this->assertSame('Jane Nakato', $customer->name);
        $this->assertSame('0772123456', $customer->phone);
        $this->assertSame('Plot 4, Ntinda', $customer->address);
    }

    public function test_a_wrong_current_password_is_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)->from(route('profile.edit'))->put(route('profile.password'), [
            'current_password' => 'nope', 'password' => 'NewPass456', 'password_confirmation' => 'NewPass456',
        ])->assertSessionHasErrorsIn('password', 'current_password');

        $this->assertTrue(Hash::check('OldPass123', $user->fresh()->password));
    }

    public function test_a_weak_new_password_is_rejected(): void
    {
        $this->actingAs($this->user())->from(route('profile.edit'))->put(route('profile.password'), [
            'current_password' => 'OldPass123', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasErrorsIn('password', 'password');
    }

    public function test_changing_the_password_signs_out_other_devices(): void
    {
        $user = $this->user();
        $user->createToken('phone');
        $user->createToken('NSales Native Mobile (biometric sign-in)');

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'OldPass123', 'password' => 'NewPass456', 'password_confirmation' => 'NewPass456',
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewPass456', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertAuthenticatedAs($user);
    }

    public function test_changing_the_password_ends_other_browser_sessions(): void
    {
        config(['session.driver' => 'database']);
        $user = $this->user();
        DB::table('sessions')->insert([
            'id' => 'other-browser', 'user_id' => $user->id, 'ip_address' => '10.0.0.2',
            'user_agent' => 'Firefox', 'payload' => '', 'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'OldPass123', 'password' => 'NewPass456', 'password_confirmation' => 'NewPass456',
        ]);

        $this->assertDatabaseMissing('sessions', ['id' => 'other-browser']);
    }

    public function test_profile_is_split_into_tabs_starting_on_details(): void
    {
        $html = $this->actingAs($this->user())->get(route('profile.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('role="tablist"', $html);
        foreach (['details', 'password', 'security'] as $tab) {
            $this->assertStringContainsString('id="profile-tab-'.$tab.'"', $html);
            $this->assertStringContainsString('id="profile-panel-'.$tab.'"', $html);
        }
        $this->assertStringContainsString('data-start="details"', $html);
        $this->assertMatchesRegularExpression('/id="profile-panel-password"[^>]*class="hidden"/', $html);
    }

    public function test_password_errors_reopen_the_password_tab(): void
    {
        $user = $this->user();

        $this->actingAs($user)->from(route('profile.edit'))->followingRedirects()->put(route('profile.password'), [
            'current_password' => 'nope', 'password' => 'NewPass456', 'password_confirmation' => 'NewPass456',
        ])->assertSee('data-start="password"', false)
          ->assertSee('Your current password is not correct.');
    }

    public function test_a_changed_password_returns_to_the_password_tab(): void
    {
        $this->actingAs($this->user())->followingRedirects()->put(route('profile.password'), [
            'current_password' => 'OldPass123', 'password' => 'NewPass456', 'password_confirmation' => 'NewPass456',
        ])->assertSee('data-start="password"', false);
    }
}
