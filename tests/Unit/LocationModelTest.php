<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Location;
use App\Models\Field;
use App\Models\Sport;
use App\Models\Membership;
use App\Models\Admin;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocationModelTest extends TestCase
{
    use RefreshDatabase;

#[Test]
    public function it_has_many_fields()
    {
        $location = Location::factory()->create();
        Field::factory()->count(3)->create(['locationId' => $location->locationId]);

        $this->assertCount(3, $location->fields);
    }

#[Test]
    public function it_has_many_memberships()
    {
        $location = Location::factory()->create();
        Membership::factory()->count(2)->create(['locationId' => $location->locationId]);

        $this->assertCount(2, $location->memberships);
    }

#[Test]
    public function it_has_many_admins()
    {
        $location = Location::factory()->create();
        Admin::factory()->count(2)->create(['locationId' => $location->locationId]);

        $this->assertCount(2, $location->admins);
    }

#[Test]
    public function it_can_filter_by_sport_through_fields_relationship()
    {
        $sport = Sport::factory()->create(['sportName' => 'Football']);
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();

        Field::factory()->create(['locationId' => $location1->locationId, 'sportId' => $sport->sportId]);

        $results = Location::hasSport(['Football'])->get();
        $this->assertCount(1, $results);
        $this->assertEquals($location1->locationId, $results->first()->locationId);
    }

#[Test]
    public function it_can_search_by_name_or_description()
    {
        Location::factory()->create(['locationName' => 'Jakarta Sports Center', 'description' => 'Main facility']);
        Location::factory()->create(['locationName' => 'Surabaya Arena', 'description' => 'Secondary facility']);

        $results = Location::search('Jakarta')->get();
        $this->assertCount(1, $results);

        $results = Location::search('facility')->get();
        $this->assertCount(2, $results);
    }

#[Test]
    public function it_uses_soft_deletes()
    {
        $location = Location::factory()->create();
        $location->delete();

        $this->assertNotNull($location->deleted_at);
        $this->assertNotNull(Location::withTrashed()->find($location->locationId));
        $this->assertNull(Location::find($location->locationId));
    }

#[Test]
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = ['locationName', 'description', 'locationPath', 'address'];
        $location = new Location();

        $this->assertEquals($expectedFillable, $location->getFillable());
    }
}
