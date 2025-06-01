<?php

use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

use Tests\TestCase;

// uses(TestCase::class)->in(__DIR__);

uses(RefreshDatabase::class);

it('can register a user', function () {
    $response = test()->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '08123456789',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'User successfully registered',
        ]);
});

it('can login a user', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = test()->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'email', 'name', 'role', 'phone'],
            'token',
        ]);
});

it('can update admin profile', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $admin = Admin::factory()->create(['userId' => $user->userId]);

    $token = JWTAuth::fromUser($user);

    $response = test()->withHeaders([
        'Authorization' => "Bearer $token",
    ])->putJson("/api/editAdminProfile/{$admin->adminId}", [
        'name' => 'Updated Name',
        'phone' => '08111111111',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Admin berhasil diperbarui',
        ]);
});

it('can change password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword'),
    ]);

    $token = JWTAuth::fromUser($user);

    $response = test()->withHeaders([
        'Authorization' => "Bearer $token",
    ])->postJson('/api/change-password', [
        'oldPassword' => 'oldpassword',
        'newPassword' => 'newpassword123',
    ]);


    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Password berhasil diubah',
        ]);
});
