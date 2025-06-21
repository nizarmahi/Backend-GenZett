<?php

// use App\Models\User;
// use App\Models\Field;
// use App\Models\Time;
// use App\Models\Location;
// use App\Models\Sport;
// use App\Models\Reservation;
// use App\Models\ReservationDetail;
// use App\Models\Payment;
// use Illuminate\Foundation\Testing\RefreshDatabase;

// uses(RefreshDatabase::class);

// beforeEach(function () {
//     $this->user = User::factory()->create();
//     $this->location = Location::factory()->create();
//     $this->sport = Sport::factory()->create();
//     $this->field = Field::factory()->create([
//         'locationId' => $this->location->locationId,
//         'sportId' => $this->sport->sportId,
//     ]);
//     $this->times = Time::factory()->count(5)->create([
//         'fieldId' => $this->field->fieldId,
//         'status' => 'available',
//     ]);
// });

// it('can get all reservations with pagination', function () {
//     Reservation::factory()->count(15)->create(['userId' => $this->user->userId]);

//     $response = $this->getJson('/api/reservations?page=2&limit=5');

//     $response->assertOk()
//         ->assertJsonStructure([
//             'success',
//             'message',
//             'data' => [
//                 '*' => [
//                     'reservationId',
//                     'name',
//                     'paymentStatus',
//                     'total',
//                     'created_at',
//                     'status',
//                     'details' => [
//                         '*' => [
//                             'fieldName',
//                             'time',
//                             'date',
//                         ]
//                     ]
//                 ]
//             ]
//         ])
//         ->assertJsonCount(5, 'data');
// });

// it('can create a reservation and update time status to non-available', function () {
//     $data = [
//         'userId' => $this->user->userId,
//         'details' => [
//             [
//                 'fieldId' => $this->field->fieldId,
//                 'timeIds' => [$this->times[0]->timeId, $this->times[1]->timeId],
//                 'date' => now()->addDay()->format('Y-m-d'),
//             ]
//         ],
//         'name' => 'Test Reservation',
//         'paymentStatus' => 'pending',
//         'total' => 200000,
//     ];

//     $response = $this->postJson('/api/reservations', $data);

//     $response->assertCreated()
//         ->assertJson([
//             'success' => true,
//             'message' => 'Reservasi berhasil dibuat',
//         ]);

//     // Verify time slots were updated to non-available
//     expect(Time::find($this->times[0]->timeId)->status)->toBe('non-available')
//         ->and(Time::find($this->times[1]->timeId)->status)->toBe('non-available');
// });

// it('prevents double booking for non-available time slots', function () {
//     // First reservation
//     $reservation = Reservation::factory()->create(['userId' => $this->user->userId]);
//     ReservationDetail::factory()->create([
//         'reservationId' => $reservation->reservationId,
//         'fieldId' => $this->field->fieldId,
//         'timeId' => $this->times[0]->timeId,
//         'date' => now()->addDay()->format('Y-m-d'),
//     ]);
//     $this->times[0]->update(['status' => 'non-available']);

//     // Attempt to book same time slot
//     $data = [
//         'userId' => $this->user->userId,
//         'details' => [
//             [
//                 'fieldId' => $this->field->fieldId,
//                 'timeIds' => [$this->times[0]->timeId],
//                 'date' => now()->addDay()->format('Y-m-d'),
//             ]
//         ],
//     ];

//     $response = $this->postJson('/api/reservations', $data);

//     $response->assertStatus(409)
//         ->assertJson([
//             'success' => false,
//             'message' => 'Beberapa lapangan dan jam sudah dipesan',
//         ])
//         ->assertJsonStructure(['conflicts']);
// });

// it('can update payment status and revert time slots to available when payment fails', function () {
//     $reservation = Reservation::factory()->create([
//         'userId' => $this->user->userId,
//         'paymentStatus' => 'pending',
//     ]);

//     $detail = ReservationDetail::factory()->create([
//         'reservationId' => $reservation->reservationId,
//         'fieldId' => $this->field->fieldId,
//         'timeId' => $this->times[0]->timeId,
//     ]);

//     // Set time to non-available initially
//     $this->times[0]->update(['status' => 'non-available']);

//     // Test failed payment
//     $response = $this->putJson("/api/reservations/{$reservation->reservationId}/payment-status", [
//         'paymentStatus' => 'fail',
//     ]);

//     $response->assertOk()
//         ->assertJson([
//             'success' => true,
//             'message' => 'Status pembayaran reservasi berhasil diperbarui',
//         ]);

//     // Time should revert to available
//     expect(Time::find($this->times[0]->timeId)->status)->toBe('available');
// });

// it('returns reservation details with all relationships', function () {
//     $reservation = Reservation::factory()->create(['userId' => $this->user->userId]);
//     ReservationDetail::factory()->create([
//         'reservationId' => $reservation->reservationId,
//         'fieldId' => $this->field->fieldId,
//         'timeId' => $this->times[0]->timeId,
//     ]);

//     $response = $this->getJson("/api/reservations/{$reservation->reservationId}");

//     $response->assertOk()
//         ->assertJson([
//             'success' => true,
//             'message' => 'Reservation details retrieved successfully',
//         ])
//         ->assertJsonStructure([
//             'data' => [
//                 'details' => [
//                     '*' => [
//                         'field' => [
//                             'location',
//                             'sport'
//                         ],
//                         'time'
//                     ]
//                 ],
//                 'user',
//                 'payment'
//             ]
//         ]);
// });

// it('returns 404 for nonexistent reservation', function () {
//     $response = $this->getJson('/api/reservations/9999');

//     $response->assertStatus(404)
//         ->assertJson([
//             'success' => false,
//             'message' => 'Reservasi tidak ditemukan. Detail Reservasi',
//         ]);
// });

// it('can get all locations with sports and pricing', function () {
//     $response = $this->getJson('/api/reservations/location');

//     $response->assertOk()
//         ->assertJsonStructure([
//             'success',
//             'data' => [
//                 '*' => [
//                     'locationId',
//                     'locationName',
//                     'address',
//                     'imageUrl',
//                     'price',
//                     'sports',
//                     'sportCount',
//                 ]
//             ]
//         ]);
// });

// it('can get all available sports', function () {
//     $response = $this->getJson('/api/reservations/sports');

//     $response->assertOk()
//         ->assertJsonStructure([
//             'success',
//             'data',
//         ]);
// });

// it('can get sports by location', function () {
//     $response = $this->getJson("/api/reservations/locations/sport/{$this->location->locationId}");

//     $response->assertOk()
//         ->assertJsonStructure([
//             'success',
//             'data' => [
//                 '*' => [
//                     'sportId',
//                     'sportName',
//                 ]
//             ]
//         ]);
// });

// it('can get schedule by location and sport', function () {
//     $response = $this->getJson("/api/reservations/locations/{$this->location->locationId}/schedule?sportName={$this->sport->sportName}");

//     $response->assertOk()
//         ->assertJsonStructure([
//             'success',
//             'start_date',
//             'end_date',
//             'locationId',
//             'sportName',
//             'fields' => [
//                 '*' => [
//                     'fieldId',
//                     'fieldName',
//                     'description',
//                     'dailySchedules' => [
//                         '*' => [
//                             'date',
//                             'schedules' => [
//                                 '*' => [
//                                     'timeId',
//                                     'time',
//                                     'price',
//                                     'status',
//                                 ]
//                             ]
//                         ]
//                     ]
//                 ]
//             ]
//         ]);
// });

// it('can get min price by location', function () {
//     $response = $this->getJson("/api/reservations/locations/{$this->location->locationId}/min-price");

//     $response->assertOk()
//         ->assertJsonStructure([
//             'success',
//             'locationId',
//             'minPrice',
//         ]);
// });

// it('can get min price per location', function () {
//     $response = $this->getJson('/api/reservations/locations/min-prices');

//     $response->assertOk()
//         ->assertJsonStructure([
//             'success',
//             'data' => [
//                 '*' => [
//                     'locationId',
//                     'locationName',
//                     'address',
//                     'imageUrl',
//                     'minPrice',
//                 ]
//             ]
//         ]);
// });

// it('can get user reservations', function () {
//     Reservation::factory()->count(3)->create(['userId' => $this->user->userId]);

//     $response = $this->getJson("/api/reservations/user-reservations?user_id={$this->user->userId}");

//     $response->assertOk()
//         ->assertJsonStructure([
//             'success',
//             'message',
//             'user',
//             'data' => [
//                 '*' => [
//                     'reservationId',
//                     'userId',
//                     'name',
//                     'paymentStatus',
//                     'total',
//                     'remaining',
//                     'created_at',
//                     'updated_at',
//                 ]
//             ]
//         ]);
// });

// it('requires user_id parameter for user reservations', function () {
//     $response = $this->getJson('/api/reservations/user-reservations');

//     $response->assertStatus(400)
//         ->assertJson([
//             'success' => false,
//             'message' => 'Parameter user_id wajib diisi.',
//         ]);
// });

// it('can confirm payment for DP reservations', function () {
//     $reservation = Reservation::factory()->create([
//         'userId' => $this->user->userId,
//         'paymentStatus' => 'dp',
//         'total' => 200000,
//     ]);

//     $response = $this->postJson("/api/reservations/{$reservation->reservationId}/confirm-payment");

//     $response->assertOk()
//         ->assertJson([
//             'message' => 'Pembayaran berhasil dikonfirmasi',
//         ])
//         ->assertJsonStructure(['reservation', 'payment']);

//     $this->assertDatabaseHas('reservations', [
//         'reservationId' => $reservation->reservationId,
//         'paymentStatus' => 'complete',
//     ]);
// });
