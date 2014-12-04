<?php
namespace BeatSwitch\Lock\Roles;

use BeatSwitch\Lock\Lock;
use BeatSwitch\Lock\Manager;
use BeatSwitch\Lock\Permissions\Permission;
use BeatSwitch\Lock\Resources\Resource;

class RoleLock extends Lock
{
    /**
     * @var \BeatSwitch\Lock\Roles\Role
     */
    protected $role;

    /**
     * @param \BeatSwitch\Lock\Roles\Role $role
     * @param \BeatSwitch\Lock\Manager $manager
     */
    public function __construct(Role $role, Manager $manager)
    {
        $this->role = $role;
        $this->manager = $manager;
    }

    /**
     * Determine if an action is allowed
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Resources\Resource $resource
     * @return bool
     */
    protected function resolvePermissions($action, Resource $resource)
    {
        $permissions = $this->getInheritedRolePermissions($this->role);

        // Search for restrictions in the permissions. We'll do this first
        // because restrictions should override any privileges.
        if (! $this->resolveRestrictions($permissions, $action, $resource)) {
            return false;
        }

        // If there are restrictions on the roles but caller specific privileges are set, allow this to pass.
        return $this->resolvePrivileges($permissions, $action, $resource);
    }

    /**
     * Returns the permissions for the current role
     *
     * @return \BeatSwitch\Lock\Permissions\Permission[]
     */
    protected function getPermissions()
    {
        return $this->getPermissionsForRole($this->role);
    }

    /**
     * Returns the permissions for the current role
     *
     * @param \BeatSwitch\Lock\Roles\Role $role
     * @return \BeatSwitch\Lock\Permissions\Permission[]
     */
    protected function getPermissionsForRole(Role $role)
    {
        return $this->getDriver()->getRolePermissions($role);
    }

    /**
     * Stores a permission into the driver
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     */
    protected function storePermission(Permission $permission)
    {
        // Don't re-store the permission if it already exists.
        if (! $this->hasPermission($permission)) {
            $this->getDriver()->storeRolePermission($this->role, $permission);
        }
    }

    /**
     * Removes a permission from the driver
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     */
    protected function removePermission(Permission $permission)
    {
        $this->getDriver()->removeRolePermission($this->role, $permission);
    }

    /**
     * Checks if a caller has a specific permission
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     * @return bool
     */
    protected function hasPermission(Permission $permission)
    {
        return $this->getDriver()->hasRolePermission($this->role, $permission);
    }

    /**
     * Returns all the permissions for a role and their inherited roles
     *
     * @param \BeatSwitch\Lock\Roles\Role $role
     * @return \BeatSwitch\Lock\Permissions\Permission[]
     */
    public function getInheritedRolePermissions(Role $role)
    {
        $permissions = $this->getPermissionsForRole($role);

        if ($inheritedRole = $role->getInheritedRole()) {
            if ($inheritedRole = $this->manager->convertRoleToObject($inheritedRole)) {
                $permissions = array_merge($permissions, $this->getInheritedRolePermissions($inheritedRole));
            }
        }

        return $permissions;
    }

    /**
     * @return \BeatSwitch\Lock\Roles\Role
     */
    public function getSubject()
    {
        return $this->getRole();
    }

    /**
     * The current caller for this Lock object
     *
     * @return \BeatSwitch\Lock\Roles\Role
     */
    public function getRole()
    {
        return $this->role;
    }
}
