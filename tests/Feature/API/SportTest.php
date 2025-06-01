<?php

use App\Models\Sport;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Bisa digunakan untuk setup awal
});

it('can list sports', function () {
    Sport::factory()->count(3)->create();

    $response = $this->getJson('/api/sports');

    $response->assertOk();
    $response->assertJsonStructure([
        'success',
        'time',
        'message',
        'totalSports',
        'offset',
        'limit',
        'sports' => [
            '*' => ['sportId', 'sportName', 'description', 'countLocation']
        ]
    ]);
});

it('can search sports by name or description', function () {
    Sport::factory()->create(['sportName' => 'Futsal']);
    Sport::factory()->create(['sportName' => 'Basket']);

    $response = $this->getJson('/api/sports?search=futsal');

    $response->assertOk();
    expect($response->json('sports'))->toHaveCount(1);
});

it('can create a new sport', function () {
    $data = [
        'sportName' => 'Badminton',
        'description' => 'Indoor sport using shuttlecock'
    ];

    $response = $this->postJson('/api/sports', $data);

    $response->assertCreated();
    $response->assertJsonPath('sport.sportName', 'Badminton');
});

it('can show a sport by id', function () {
    $sport = Sport::factory()->create();

    $response = $this->getJson("/api/sports/{$sport->sportId}");

    $response->assertOk();
    $response->assertJsonPath('sport.sportId', $sport->sportId);
});

it('returns 404 for unknown sport', function () {
    $response = $this->getJson('/api/sports/999');

    $response->assertNotFound();
});

it('can update a sport', function () {
    $sport = Sport::factory()->create([
        'sportName' => 'Old Name',
        'description' => 'Old description'
    ]);

    $update = [
        'sportName' => 'New Name',
        'description' => 'Updated description'
    ];

    $response = $this->putJson("/api/sports/{$sport->sportId}", $update);

    $response->assertOk();
    $response->assertJsonPath('sport.sportName', 'New Name');
});

it('can delete a sport', function () {
    $sport = Sport::factory()->create();

    $response = $this->deleteJson("/api/sports/{$sport->sportId}");

    $response->assertOk();
    expect(Sport::find($sport->sportId))->toBeNull();
});

it('cannot delete sport if it is used by fields', function () {
    $location = Location::factory()->create();
    $sport = Sport::factory()->create();

    // Simulasikan bahwa sport ini digunakan oleh sebuah field
    DB::table('fields')->insert([
        'fieldId' => 1,
        'sportId' => $sport->sportId,
        'locationId' => $location->locationId,
        'name' => 'Lapangan A',
        'description' => 'Description',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $response = $this->deleteJson("/api/sports/{$sport->sportId}");

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => "Sport tidak dapat dihapus karena sedang digunakan oleh 1 lapangan"
    ]);
});
