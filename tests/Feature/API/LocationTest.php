<?php

use App\Models\Location;
use App\Models\Field;
use App\Models\Sport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// 1. Tampilkan semua lokasi
it('can list locations', function () {
    Location::factory()->count(3)->create();

    $response = $this->getJson('/api/locations');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'success',
                 'time',
                 'message',
                 'totalLocations',
                 'offset',
                 'limit',
                 'locations' => [['locationId', 'locationName', 'sports', 'countLap', 'description', 'address']]
             ]);
});

// 2. Buat lokasi baru (dengan gambar)
it('can create a new location', function () {
    Storage::fake('public');

    $data = [
        'locationName' => 'Test Location',
        'address' => 'Jl. Testing',
        'description' => 'Lokasi testing',
        'locationPath' => UploadedFile::fake()->image('photo.jpg'),
    ];

    $response = $this->postJson('/api/locations', $data);

    $response->assertCreated()
             ->assertJsonPath('location.locationName', 'Test Location');
});

// 3. Lihat detail lokasi
it('can show a location by id', function () {
    $location = Location::factory()->create();

    $response = $this->getJson("/api/locations/{$location->locationId}");

    $response->assertOk()
             ->assertJsonPath('location.locationId', $location->locationId);
});

// 4. Return 404 jika ID tidak ditemukan
it('returns 404 for unknown location', function () {
    $response = $this->getJson('/api/locations/9999');

    $response->assertNotFound();
});

// 5. Update lokasi
it('can update a location', function () {
    Storage::fake('public');

    $location = Location::factory()->create([
        'locationName' => 'Old Name',
    ]);

    $data = [
        'locationName' => 'New Name',
    ];

    $response = $this->putJson("/api/locations/{$location->locationId}", $data);

    $response->assertOk()
             ->assertJsonPath('location.locationName', 'New Name');
});

// 6. Hapus lokasi tanpa lapangan
it('can delete a location with no fields', function () {
    $location = Location::factory()->create();

    $response = $this->deleteJson("/api/locations/{$location->locationId}");

    $response->assertOk()
             ->assertJson(['message' => 'Lokasi berhasil dihapus']);
});

// 7. Gagal hapus lokasi jika ada lapangan
it('cannot delete location if it has fields', function () {
    $location = Location::factory()->create();
    $sport = Sport::factory()->create();

    Field::factory()->create([
        'locationId' => $location->locationId,
        'sportId' => $sport->sportId,
    ]);

    $response = $this->deleteJson("/api/locations/{$location->locationId}");

    $response->assertStatus(409)
             ->assertJson(['message' => "Lokasi tidak dapat dihapus karena sedang berisi 1 lapangan"]);
});
