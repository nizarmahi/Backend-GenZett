<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Field;
use App\Models\Location;
use App\Models\Sport;
use App\Models\Time;
use App\Models\ReservationDetail;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FieldModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_location_and_sport()
    {
        $location = Location::factory()->create();
        $sport = Sport::factory()->create();
        $field = Field::factory()->create([
            'locationId' => $location->locationId,
            'sportId' => $sport->sportId
        ]);

        $this->assertEquals($location->locationId, $field->location->locationId);
        $this->assertEquals($sport->sportId, $field->sport->sportId);
    }

#[Test]
    public function it_has_many_times()
    {
        $field = Field::factory()->create();
        Time::factory()->count(2)->create(['fieldId' => $field->fieldId]);

        $this->assertCount(2, $field->times);
    }

#[Test]
    public function it_has_many_reservation_details()
    {
        $field = Field::factory()->create();
        ReservationDetail::factory()->count(3)->create(['fieldId' => $field->fieldId]);

        $this->assertCount(3, $field->reservationDetails);
    }

#[Test]
    public function it_can_filter_by_sport()
    {
        $sport1 = Sport::factory()->create();
        $sport2 = Sport::factory()->create();
        $field1 = Field::factory()->create(['sportId' => $sport1->sportId]);
        $field2 = Field::factory()->create(['sportId' => $sport2->sportId]);

        $results = Field::hasSport([$sport1->sportId])->get();
        $this->assertCount(1, $results);
        $this->assertEquals($field1->fieldId, $results->first()->fieldId);
    }

#[Test]
    public function it_can_filter_by_location()
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $field1 = Field::factory()->create(['locationId' => $location1->locationId]);
        $field2 = Field::factory()->create(['locationId' => $location2->locationId]);

        $results = Field::hasLocation([$location1->locationId])->get();
        $this->assertCount(1, $results);
        $this->assertEquals($field1->fieldId, $results->first()->fieldId);
    }

#[Test]
    public function it_can_search_by_name()
    {
        Field::factory()->create(['name' => 'Football Field A']);
        Field::factory()->create(['name' => 'Basketball Court']);

        $results = Field::search('Football')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Football Field A', $results->first()->name);
    }

#[Test]
    public function it_uses_soft_deletes()
    {
        $field = Field::factory()->create();
        $field->delete();

        $this->assertNotNull($field->deleted_at);
        $this->assertNotNull(Field::withTrashed()->find($field->fieldId));
        $this->assertNull(Field::find($field->fieldId));
    }

#[Test]
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = ['locationId', 'sportId', 'name', 'description'];
        $field = new Field();

        $this->assertEquals($expectedFillable, $field->getFillable());
    }
}
