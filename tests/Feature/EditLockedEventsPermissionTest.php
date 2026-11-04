<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EditLockedEventsPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_group_gets_the_permission_to_edit_events_in_planning_or_done(): void
    {
        $this->assertTrue(
            Role::where('name', 'Admin')->sole()->permissions->contains('slug', 'eventos-editar-planejamento-concluido')
        );
    }
}
