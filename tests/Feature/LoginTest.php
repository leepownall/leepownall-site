<?php

use App\Models\User;
use Laravel\Passport\ClientRepository;

it('signs in and returns to the page that asked for it', function () {
    $user = User::factory()->create(['password' => 'correct horse battery']);
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('Claude', ['https://claude.ai/api/mcp/auth_callback'], false);
    $authorize = '/oauth/authorize?'.http_build_query([
        'client_id' => $client->id,
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', str_repeat('a', 64), true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
        'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'state',
    ]);

    $this->get($authorize)->assertRedirect(route('login'));

    $this->get(route('login'))->assertSuccessful();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'correct horse battery',
    ])->assertRedirect($authorize);

    $this->assertAuthenticatedAs($user);

    $this->get($authorize)
        ->assertSuccessful()
        ->assertSee('Claude');
});

it('does not sign in with the wrong password', function () {
    $user = User::factory()->create(['password' => 'correct horse battery']);

    $this->from(route('login'))
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'These details do not match our records.']);

    $this->assertGuest();
});

it('locks out after five failed attempts', function () {
    $user = User::factory()->create(['password' => 'correct horse battery']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'correct horse battery'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('creates a user from the console', function () {
    $this->artisan('users:create')
        ->expectsQuestion('Name', 'Lee Pownall')
        ->expectsQuestion('Email', 'lee@example.com')
        ->expectsQuestion('Password', 'correct horse battery')
        ->assertSuccessful();

    $this->post(route('login.store'), ['email' => 'lee@example.com', 'password' => 'correct horse battery']);

    $this->assertAuthenticated();
});
