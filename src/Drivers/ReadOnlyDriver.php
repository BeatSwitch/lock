<?php
namespace BeatSwitch\Lock\Drivers;

use BeatSwitch\Lock\Callers\Caller;
use BeatSwitch\Lock\Permissions\Permission;
use BeatSwitch\Lock\Roles\Role;

/**
 * The store and remove methods on this driver do nothing
 * and thus only the get and has methods can be used.
 */
abstract class ReadOnlyDriver implements Driver
{
    /**
     * Stores a new permission into the driver for a caller
     *
     * @param \BeatSwitch\Lock\Callers\Caller $caller
     * @param \BeatSwitch\Lock\Permissions\Permission
     * @return void
     */
    final public function storeCallerPermission(Caller $caller, Permission $permission)
    {
    }

    /**
     * Removes a permission from the driver for a caller
     *
     * @param \BeatSwitch\Lock\Callers\Caller $caller
     * @param \BeatSwitch\Lock\Permissions\Permission
     * @return void
     */
    final public function removeCallerPermission(Caller $caller, Permission $permission)
    {
    }

    /**
     * Stores a new permission for a role
     *
     * @param \BeatSwitch\Lock\Roles\Role $role
     * @param \BeatSwitch\Lock\Permissions\Permission
     * @return void
     */
    final public function storeRolePermission(Role $role, Permission $permission)
    {
    }

    /**
     * Removes a permission for a role
     *
     * @param \BeatSwitch\Lock\Roles\Role $role
     * @param \BeatSwitch\Lock\Permissions\Permission
     * @return void
     */
    final public function removeRolePermission(Role $role, Permission $permission)
    {
    }
}
