<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Sport;
use App\Models\Field;
use App\Models\Membership;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SportModelTest extends TestCase
{
    use RefreshDatabase;

#[Test]
    public function it_has_many_fields()
    {
        $sport = Sport::factory()->create();
        Field::factory()->count(3)->create(['sportId' => $sport->sportId]);

        $this->assertCount(3, $sport->fields);
    }

#[Test]
    public function it_has_many_memberships()
    {
        $sport = Sport::factory()->create();
        Membership::factory()->count(2)->create(['sportId' => $sport->sportId]);

        $this->assertCount(2, $sport->memberships);
    }

#[Test]
    public function it_uses_soft_deletes()
    {
        $sport = Sport::factory()->create();
        $sport->delete();

        $this->assertNotNull($sport->deleted_at);
        $this->assertNotNull(Sport::withTrashed()->find($sport->sportId));
        $this->assertNull(Sport::find($sport->sportId));
    }

#[Test]
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = ['sportName', 'description'];
        $sport = new Sport();

        $this->assertEquals($expectedFillable, $sport->getFillable());
    }
}
