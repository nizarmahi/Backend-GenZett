<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Membership;
use App\Models\Sport;
use App\Models\Location;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class MembershipModelTest extends TestCase
{
    use RefreshDatabase;

#[Test]
    public function it_belongs_to_a_sport()
    {
        $sport = Sport::factory()->create();
        $membership = Membership::factory()->create(['sportId' => $sport->sportId]);

        $this->assertNotNull($membership->sports);
        $this->assertEquals($sport->sportId, $membership->sports->sportId);
    }

#[Test]
    public function it_belongs_to_a_location()
    {
        $location = Location::factory()->create();
        $membership = Membership::factory()->create(['locationId' => $location->locationId]);

        $this->assertNotNull($membership->locations);
        $this->assertEquals($location->locationId, $membership->locations->locationId);
    }

#[Test]
    public function it_uses_soft_deletes()
    {
        $membership = Membership::factory()->create();
        $membership->delete();

        $this->assertNotNull($membership->deleted_at);
        $this->assertNotNull(Membership::withTrashed()->find($membership->membershipId));
        $this->assertNull(Membership::find($membership->membershipId));
    }

#[Test]
    public function it_casts_created_at_to_datetime()
    {
        $membership = Membership::factory()->create();

        $this->assertInstanceOf(Carbon::class, $membership->created_at);
    }

#[Test]
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = ['locationId', 'sportId', 'name', 'description', 'discount', 'weeks'];
        $membership = new Membership();

        $this->assertEquals($expectedFillable, $membership->getFillable());
    }
}
