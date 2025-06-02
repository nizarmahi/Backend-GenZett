<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Admin;
use App\Models\User;
use App\Models\Location;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_user_and_location()
    {
        $user = User::factory()->create();
        $location = Location::factory()->create();
        $admin = Admin::factory()->create([
            'userId' => $user->userId,
            'locationId' => $location->locationId
        ]);

        $this->assertEquals($user->userId, $admin->user->userId);
        $this->assertEquals($location->locationId, $admin->location->locationId);
    }
}
