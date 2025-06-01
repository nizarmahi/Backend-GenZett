<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Buat beberapa user dengan role 'user'
    User::factory()->count(5)->create(['role' => 'user']);
});

it('can list users with pagination and search', function () {
    // Buat user dengan nama unik supaya bisa dicari
    User::factory()->create([
        'name' => 'UniqueNameForSearch',
        'role' => 'user',
    ]);

    $response = $this->getJson('/api/users?limit=3&page=1&search=UniqueNameForSearch');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'time',
            'message',
            'totalUsers',
            'offset',
            'limit',
            'users'
        ])
        ->assertJson(['success' => true])
        ->assertJsonCount(1, 'users');
});

it('can show user detail', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->getJson("/api/users/{$user->userId}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'user' => [
                'id' => $user->userId,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]
        ]);
});

it('returns 404 if user not found on show', function () {
    $response = $this->getJson('/api/users/999999');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'User dengan ID 999999 tidak ditemukan',
        ]);
});

it('can update user', function () {
    $user = User::factory()->create(['role' => 'user']);

    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updatedemail@example.com',
        'phone' => '08123456789',
    ];

    $response = $this->putJson("/api/users/{$user->userId}", $updateData);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User berhasil diperbarui',
        ]);

    $this->assertDatabaseHas('users', [
        'userId' => $user->userId,
        'name' => 'Updated Name',
        'email' => 'updatedemail@example.com',
        'phone' => '08123456789',
    ]);
});

it('returns validation error on update with invalid data', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->putJson("/api/users/{$user->userId}", [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('returns 404 if user not found on update', function () {
    $response = $this->putJson('/api/users/999999', [
        'name' => 'Does Not Exist',
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'User dengan ID 999999 tidak ditemukan',
        ]);
});

it('can delete user', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->deleteJson("/api/users/{$user->userId}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User berhasil dihapus',
        ]);

    $this->assertSoftDeleted('users', ['userId' => $user->userId]);
});

it('returns 404 if user not found on delete', function () {
    $response = $this->deleteJson('/api/users/999999');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'User dengan ID 999999 tidak ditemukan',
        ]);
});
