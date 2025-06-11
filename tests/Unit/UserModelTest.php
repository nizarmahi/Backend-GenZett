<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\User;
use App\Models\Reservation;
use App\Models\Admin;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

#[Test]
    public function it_can_create_a_user_with_valid_attributes()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'role' => 'user'
        ]);

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('08123456789', $user->phone);
        $this->assertEquals('user', $user->role);
    }

#[Test]
    public function it_has_many_reservations()
    {
        $user = User::factory()->create();
        Reservation::factory()->count(3)->create(['userId' => $user->userId]);

        $this->assertCount(3, $user->reservations);
    }

#[Test]
    public function it_has_one_admin()
    {
        $user = User::factory()->create();
        $admin = Admin::factory()->create(['userId' => $user->userId]);

        $this->assertNotNull($user->admin);
        $this->assertEquals($admin->adminId, $user->admin->adminId);
    }

#[Test]
    public function it_can_search_users_by_name_email_or_phone()
    {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $results = User::search('John')->get();
        $this->assertCount(1, $results);

        $results = User::search('jane@example.com')->get();
        $this->assertCount(1, $results);
    }

#[Test]
    public function it_uses_soft_deletes()
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertNotNull($user->deleted_at);
        $this->assertNotNull(User::withTrashed()->find($user->userId));
        $this->assertNull(User::find($user->userId));
    }
}
