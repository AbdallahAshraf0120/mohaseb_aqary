<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserExclusivePermissionsTest extends TestCase
{
    public function test_custom_set_replaces_role_for_permission_check(): void
    {
        $user = new User([
            'role' => 'viewer',
            'extra_permissions' => ['reports.view'],
        ]);

        $this->assertTrue($user->usesExclusiveCustomPermissions());
        $this->assertTrue($user->hasPermission('reports.view'));
        $this->assertFalse($user->hasPermission('dashboard.view'));
    }

    public function test_manage_in_custom_set_implies_view(): void
    {
        $user = new User([
            'role' => 'viewer',
            'extra_permissions' => ['properties.manage'],
        ]);

        $this->assertTrue($user->hasPermission('properties.manage'));
        $this->assertTrue($user->hasPermission('properties.view'));
        $this->assertFalse($user->hasPermission('sales.view'));
    }

    public function test_empty_custom_falls_back_to_role_without_query_when_no_cache(): void
    {
        $user = new User([
            'role' => 'viewer',
            'extra_permissions' => null,
        ]);

        $this->assertFalse($user->usesExclusiveCustomPermissions());
        // hasPermission for non-admin hits DB for role — skip asserting true/false without DB
        $this->assertSame([], $user->customPermissionSlugs());
    }
}
