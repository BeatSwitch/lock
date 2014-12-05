<?php
namespace BeatSwitch\Lock\Callers;

use BeatSwitch\Lock\Lock;
use BeatSwitch\Lock\Manager;
use BeatSwitch\Lock\Permissions\Permission;
use BeatSwitch\Lock\Resources\Resource;

class CallerLock extends Lock
{
    /**
     * @var \BeatSwitch\Lock\Callers\Caller
     */
    protected $caller;

    /**
     * @param \BeatSwitch\Lock\Callers\Caller $caller
     * @param \BeatSwitch\Lock\Manager $manager
     */
    public function __construct(Caller $caller, Manager $manager)
    {
        $this->caller = $caller;
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
        $permissions = $this->getPermissions();

        // Search for restrictions in the permissions. We'll do this first
        // because restrictions should override any privileges.
        if (! $this->resolveRestrictions($permissions, $action, $resource)) {
            return false;
        }

        // Check if one of the caller's roles has permission to do the action on the resource.
        foreach ($this->getLockInstancesForCallerRoles() as $roleLock) {
            if ($roleLock->can($action, $resource)) {
                return true;
            }
        }

        // If no restrictions are found, pass when a privilege is found on either the roles or caller.
        return $this->resolvePrivileges($permissions, $action, $resource);
    }

    /**
     * Returns the permissions for the current caller
     *
     * @return \BeatSwitch\Lock\Permissions\Permission[]
     */
    protected function getPermissions()
    {
        return $this->getDriver()->getCallerPermissions($this->caller);
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
            $this->getDriver()->storeCallerPermission($this->caller, $permission);
        }
    }

    /**
     * Removes a permission from the driver
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     */
    protected function removePermission(Permission $permission)
    {
        $this->getDriver()->removeCallerPermission($this->caller, $permission);
    }

    /**
     * Checks if a caller has a specific permission
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     * @return bool
     */
    protected function hasPermission(Permission $permission)
    {
        return $this->getDriver()->hasCallerPermission($this->caller, $permission);
    }

    /**
     * Get all the lock instances for all the roles of the current caller
     *
     * @return \BeatSwitch\Lock\Roles\RoleLock[]
     */
    protected function getLockInstancesForCallerRoles()
    {
        return array_map(function ($role) {
            return $this->manager->role($role);
        }, $this->caller->getCallerRoles());
    }

    /**
     * @return \BeatSwitch\Lock\Callers\Caller
     */
    public function getSubject()
    {
        return $this->getCaller();
    }

    /**
     * The current caller for this Lock object
     *
     * @return \BeatSwitch\Lock\Callers\Caller
     */
    public function getCaller()
    {
        return $this->caller;
    }
}
