<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Payment;
use App\Models\Reservation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PaymentModelTest extends TestCase
{
    use RefreshDatabase;

#[Test]
    public function it_belongs_to_a_reservation()
    {
        $reservation = Reservation::factory()->create();
        $payment = Payment::factory()->create(['reservationId' => $reservation->reservationId]);

        $this->assertNotNull($payment->reservation);
        $this->assertEquals($reservation->reservationId, $payment->reservation->reservationId);
    }

#[Test]
    public function it_casts_invoice_date_and_expiry_date_to_datetime()
    {
        $payment = Payment::factory()->create([
            'invoiceDate' => '2024-01-15 10:30:00',
            'expiry_date' => '2024-01-20 23:59:59'
        ]);

        $this->assertInstanceOf(Carbon::class, $payment->invoiceDate);
        $this->assertInstanceOf(Carbon::class, $payment->expiry_date);
    }

#[Test]
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = [
            'reservationId',
            'invoiceDate',
            'totalPaid',
            'xendit_invoice_id',
            'xendit_invoice_url',
            'xendit_status',
            'expiry_date',
        ];

        $payment = new Payment();
        $this->assertEquals($expectedFillable, $payment->getFillable());
    }
}
