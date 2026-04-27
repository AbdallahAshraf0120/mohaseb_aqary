<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable) {
            $this->markTestSkipped('قاعدة بيانات الاختبار غير متاحة (مثلاً بدون pdo_sqlite).');
        }
        $this->seed(PermissionSeeder::class);
    }

    public function test_viewer_can_open_properties_index_but_not_create(): void
    {
        $project = Project::query()->create([
            'name' => 'Test Project',
            'code' => 'tp',
            'is_active' => true,
            'is_draft' => false,
        ]);

        $viewer = User::factory()->create([
            'email' => 'viewer@test.local',
            'role' => 'viewer',
        ]);

        $this->actingAs($viewer)
            ->get(route('properties.index', $project))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('properties.create', $project))
            ->assertForbidden();
    }

    public function test_accountant_can_open_property_create(): void
    {
        $project = Project::query()->create([
            'name' => 'Acct Project',
            'code' => 'ap',
            'is_active' => true,
            'is_draft' => false,
        ]);

        $user = User::factory()->create([
            'email' => 'acct@test.local',
            'role' => 'accountant',
        ]);

        $this->actingAs($user)
            ->get(route('properties.create', $project))
            ->assertOk();
    }

    public function test_viewer_cannot_open_activity_log(): void
    {
        $viewer = User::factory()->create([
            'email' => 'viewer-act@test.local',
            'role' => 'viewer',
        ]);

        $this->actingAs($viewer)
            ->get(route('activity-log.index'))
            ->assertForbidden();
    }

    public function test_accountant_can_open_activity_log(): void
    {
        $user = User::factory()->create([
            'email' => 'acct-act@test.local',
            'role' => 'accountant',
        ]);

        $this->actingAs($user)
            ->get(route('activity-log.index'))
            ->assertOk();
    }
}
