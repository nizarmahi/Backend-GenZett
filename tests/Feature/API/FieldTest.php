<?php

use App\Models\Field;
use App\Models\Location;
use App\Models\Sport;
use App\Models\Time;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sport = Sport::factory()->create();
    $this->location = Location::factory()->create();
});

// Test untuk index (list) dengan filter dan pagination
it('can list fields with pagination and filters', function () {
    // Buat beberapa field
    Field::factory()->count(5)->create([
        'sportId' => $this->sport->sportId,
        'locationId' => $this->location->locationId,
    ]);

    $response = $this->getJson('/api/fields?limit=3&page=1&sports=' . $this->sport->sportId);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'time',
            'message',
            'totalFields',
            'offset',
            'limit',
            'fields'
        ])
        ->assertJson(['success' => true])
        ->assertJsonCount(3, 'fields');
});

// Test untuk show detail field
it('can show a field detail', function () {
    $field = Field::factory()->create([
        'sportId' => $this->sport->sportId,
        'locationId' => $this->location->locationId,
    ]);

    // Buat times untuk field ini
    Time::factory()->count(3)->create([
        'fieldId' => $field->fieldId,
        'status' => 'available',
        'time' => '08:00:00'
    ]);

    $response = $this->getJson("/api/fields/{$field->fieldId}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'field' => [
                'id' => $field->fieldId,
                'name' => $field->name,
                'location' => $this->location->locationId,
                'sport' => $this->sport->sportId,
            ]
        ]);
});

// Test store (create) field
it('can create a new field', function () {
    $data = [
        'locationId' => $this->location->locationId,
        'sportId' => $this->sport->sportId,
        'name' => 'Lapangan Baru',
        'startHour' => '00:00',
        'endHour' => '23:59',
        'price' => 150000,
        'description' => 'Deskripsi lapangan baru',
    ];

    $response = $this->postJson('/api/fields', $data);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('fields', ['name' => 'Lapangan Baru']);
});

// Test update field
it('can update an existing field', function () {
    $field = Field::factory()->create([
        'sportId' => $this->sport->sportId,
        'locationId' => $this->location->locationId,
    ]);

    $updateData = [
        'locationId' => $this->location->locationId,
        'sportId' => $this->sport->sportId,
        'name' => 'Nama Lapangan Updated',
        'startHour' => '00:00',
        'endHour' => '23:59',
        'price' => 150000,
        'description' => 'Deskripsi updated',
    ];

    $response = $this->putJson("/api/fields/{$field->fieldId}", $updateData);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Lapangan berhasil diperbarui',
        ]);

    $this->assertDatabaseHas('fields', ['fieldId' => $field->fieldId, 'name' => 'Nama Lapangan Updated']);
});

it('can delete a field if no reservationDetails', function () {
    $field = Field::factory()->create([
        'sportId' => $this->sport->sportId,
        'locationId' => $this->location->locationId,
    ]);

    $response = $this->deleteJson("/api/fields/{$field->fieldId}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Lapangan berhasil dihapus',
        ]);

    $this->assertSoftDeleted('fields', ['fieldId' => $field->fieldId]);
});

it('cannot delete field if reservationDetails exist', function () {
    $field = Field::factory()->create([
        'sportId' => $this->sport->sportId,
        'locationId' => $this->location->locationId,
    ]);

    // Buat waktu (time) untuk field
    $time = Time::factory()->create([
        'fieldId' => $field->fieldId,
        'status' => 'available',
        'time' => '08:00:00',
        'price' => 10000,
    ]);

    // Buat reservation agar valid untuk reservationDetails
    $reservation = Reservation::factory()->create();

    // Buat reservationDetails yang terkait dengan field
    $field->reservationDetails()->create([
        'reservationId' => $reservation->reservationId,
        'timeId' => $time->timeId,
        'date' => now()->toDateString(),
    ]);

    $response = $this->deleteJson("/api/fields/{$field->fieldId}");

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => 'Tidak dapat menghapus lapangan dengan laporan yang ada',
        ]);
});
