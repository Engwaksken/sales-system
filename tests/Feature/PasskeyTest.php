<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laragear\WebAuthn\Models\WebAuthnCredential;
use Tests\TestCase;

class PasskeyTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'manager'): User
    {
        $branch = Branch::query()->first() ?? Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);

        return User::create([
            'name' => 'Jane Akello', 'email' => Str::lower(Str::random(8)).'@example.com',
            'password' => bcrypt('password'), 'role' => $role, 'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);
    }

    private function passkeyFor(User $user, string $alias = 'My phone'): WebAuthnCredential
    {
        return $user->webAuthnCredentials()->forceCreate([
            'id' => Str::random(40),
            'user_id' => (string) Str::uuid(),
            'alias' => $alias,
            'counter' => 0,
            'rp_id' => 'localhost',
            'origin' => 'http://localhost',
            'public_key' => 'test-public-key',
            'attestation_format' => 'none',
        ]);
    }

    public function test_sign_in_page_offers_passkey_sign_in(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('data-passkey-login', false)
            ->assertSee('Sign in with fingerprint or face')
            ->assertSee('js/passkeys.js', false);
    }

    public function test_sign_in_challenge_requires_the_person_to_be_verified(): void
    {
        $this->postJson(route('passkeys.login.options'))
            ->assertOk()
            ->assertJsonStructure(['challenge', 'timeout'])
            ->assertJsonPath('userVerification', 'required');
    }

    public function test_an_unknown_passkey_does_not_sign_in(): void
    {
        $this->postJson(route('passkeys.login.options'))->assertOk();

        $this->postJson(route('passkeys.login'), [
            'id' => 'abc', 'rawId' => 'abc', 'type' => 'public-key',
            'response' => ['authenticatorData' => 'abc', 'clientDataJSON' => 'abc', 'signature' => 'abc'],
        ])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_signed_in_users_cannot_use_the_sign_in_endpoints(): void
    {
        $this->actingAs($this->user())->postJson(route('passkeys.login.options'))->assertRedirect();
    }

    public function test_guests_cannot_manage_passkeys(): void
    {
        $this->get(route('passkeys.index'))->assertRedirect(route('login'));
        $this->postJson(route('passkeys.options'))->assertUnauthorized();
    }

    public function test_passkey_page_lists_only_your_own_passkeys(): void
    {
        $me = $this->user();
        $this->passkeyFor($me, 'Chrome on Android');
        $this->passkeyFor($this->user(), 'Someone else laptop');

        $this->actingAs($me)->get(route('passkeys.index'))
            ->assertOk()
            ->assertSee('Chrome on Android')
            ->assertDontSee('Someone else laptop')
            ->assertSee('Add passkey');
    }

    public function test_profile_links_to_the_passkey_page(): void
    {
        $this->actingAs($this->user())->get(route('profile.edit'))
            ->assertSee('href="'.route('passkeys.index').'"', false);
    }

    public function test_new_passkeys_must_verify_the_person_and_stay_on_the_device(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson(route('passkeys.options'))
            ->assertOk()
            ->assertJsonStructure(['challenge', 'rp', 'user' => ['id', 'name', 'displayName'], 'pubKeyCredParams'])
            ->assertJsonPath('user.name', $user->email)
            ->assertJsonPath('authenticatorSelection.userVerification', 'required')
            ->assertJsonPath('authenticatorSelection.residentKey', 'required');
    }

    public function test_you_can_remove_your_own_passkey(): void
    {
        $me = $this->user();
        $passkey = $this->passkeyFor($me);

        $this->actingAs($me)->delete(route('passkeys.destroy', $passkey->id))
            ->assertRedirect(route('passkeys.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('webauthn_credentials', ['id' => $passkey->id]);
    }

    public function test_you_cannot_remove_someone_elses_passkey(): void
    {
        $theirs = $this->passkeyFor($this->user());

        $this->actingAs($this->user())->delete(route('passkeys.destroy', $theirs->id))->assertNotFound();

        $this->assertDatabaseHas('webauthn_credentials', ['id' => $theirs->id]);
    }

    public function test_password_sign_in_still_works_with_the_passkey_user_provider(): void
    {
        $user = $this->user();

        $this->assertTrue(auth()->validate(['email' => $user->email, 'password' => 'password']));
        $this->assertFalse(auth()->validate(['email' => $user->email, 'password' => 'wrong']));
    }
}
