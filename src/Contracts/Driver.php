<?php
namespace BeatSwitch\Lock\Contracts;

/**
 * A contract to identify an implementation to store permissions to
 *
 * Drivers can both be persistent or static depending on their implementation.
 * A default, static ArrayDriver implementation comes with this package.
 */
interface Driver
{
    /**
     * Returns all the permissions for a caller
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @return \BeatSwitch\Lock\Contracts\Permission[]
     */
    public function getPermissions(Caller $caller);

    /**
     * Stores a new permission for a caller
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return void
     */
    public function storePermission(Caller $caller, Permission $permission);

    /**
     * Removes a permission for a caller
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return void
     */
    public function removePermission(Caller $caller, Permission $permission);

    /**
     * Checks if a permission is stored for a caller
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return bool
     */
    public function hasPermission(Caller $caller, Permission $permission);

    /**
     * Returns all the permissions for a role
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @return \BeatSwitch\Lock\Contracts\Permission[]
     */
    public function getRolePermissions(Role $role);

    /**
     * Stores a new permission for a role
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return void
     */
    public function storeRolePermission(Role $role, Permission $permission);

    /**
     * Removes a permission for a role
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return void
     */
    public function removeRolePermission(Role $role, Permission $permission);

    /**
     * Checks if a permission is stored for a role
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return bool
     */
    public function hasRolePermission(Role $role, Permission $permission);
}
