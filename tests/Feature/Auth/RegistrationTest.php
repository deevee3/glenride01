<?php

use App\Models\Tenant;
use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'tenant_name' => 'Test Company',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $tenant = Tenant::where('name', 'Test Company')->first();
    expect($tenant)->not->toBeNull();

    $user = User::where('email', 'test@example.com')->first();
    expect($user)
        ->not->toBeNull()
        ->and($user->tenant_id)
        ->toBe($tenant->id);
});