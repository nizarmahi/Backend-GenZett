<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\ReservationDetail;
use App\Models\Reservation;
use App\Models\Field;
use App\Models\Time;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReservationDetailModelTest extends TestCase
{
    use RefreshDatabase;

#[Test]
    public function it_belongs_to_reservation_field_and_time()
    {
        $reservation = Reservation::factory()->create();
        $field = Field::factory()->create();
        $time = Time::factory()->create();

        $detail = ReservationDetail::factory()->create([
            'reservationId' => $reservation->reservationId,
            'fieldId' => $field->fieldId,
            'timeId' => $time->timeId
        ]);

        $this->assertEquals($reservation->reservationId, $detail->reservation->reservationId);
        $this->assertEquals($field->fieldId, $detail->field->fieldId);
        $this->assertEquals($time->timeId, $detail->time->timeId);
    }

#[Test]
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = ['reservationId', 'fieldId', 'timeId', 'date'];
        $detail = new ReservationDetail();

        $this->assertEquals($expectedFillable, $detail->getFillable());
    }
}
