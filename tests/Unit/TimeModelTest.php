<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Time;
use App\Models\Field;
use App\Models\ReservationDetail;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimeModelTest extends TestCase
{
    use RefreshDatabase;

#[Test]
    public function it_belongs_to_a_field()
    {
        $field = Field::factory()->create();
        $time = Time::factory()->create(['fieldId' => $field->fieldId]);

        $this->assertEquals($field->fieldId, $time->field->fieldId);
    }

#[Test]
    public function it_has_many_reservation_details()
    {
        $time = Time::factory()->create();
        ReservationDetail::factory()->count(2)->create(['timeId' => $time->timeId]);

        $this->assertCount(2, $time->reservationDetails);
    }

#[Test]
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = ['fieldId', 'time', 'status', 'price'];
        $time = new Time();

        $this->assertEquals($expectedFillable, $time->getFillable());
    }
}
