<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Reservation;
use App\Models\User;
use App\Models\ReservationDetail;
use App\Models\Payment;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReservationModelTest extends TestCase
{
    use RefreshDatabase;

#[Test]
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create(['userId' => $user->userId]);

        $this->assertNotNull($reservation->user);
        $this->assertEquals($user->userId, $reservation->user->userId);
    }

#[Test]
    public function it_has_many_reservation_details()
    {
        $reservation = Reservation::factory()->create();
        ReservationDetail::factory()->count(2)->create(['reservationId' => $reservation->reservationId]);

        $this->assertCount(2, $reservation->details);
    }

#[Test]
    public function it_has_one_payment()
    {
        $reservation = Reservation::factory()->create();
        $payment = Payment::factory()->create(['reservationId' => $reservation->reservationId]);

        $this->assertNotNull($reservation->payment);
        $this->assertEquals($payment->paymentId, $reservation->payment->paymentId);
    }

#[Test]
    public function it_has_correct_fillable_attributes()
    {
        $fillable = ['userId', 'name', 'paymentStatus', 'total'];
        $reservation = new Reservation();

        $this->assertEquals($fillable, $reservation->getFillable());
    }
}
