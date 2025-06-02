<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\ReservationMembership;
use App\Models\Reservation;
use App\Models\Membership;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReservationMembershipModelTest extends TestCase
{
    use RefreshDatabase;

#[Test]
    public function it_belongs_to_reservation_and_membership()
    {
        $reservation = Reservation::factory()->create();
        $membership = Membership::factory()->create();

        $reservationMembership = ReservationMembership::factory()->create([
            'reservationId' => $reservation->reservationId,
            'membershipId' => $membership->membershipId
        ]);

        $this->assertEquals($reservation->reservationId, $reservationMembership->reservation->reservationId);
        $this->assertEquals($membership->membershipId, $reservationMembership->membership->membershipId);
    }

#[Test]
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = ['reservationId', 'membershipId'];
        $reservationMembership = new ReservationMembership();

        $this->assertEquals($expectedFillable, $reservationMembership->getFillable());
    }
}
