<?php
namespace BeatSwitch\Lock;

use BeatSwitch\Lock\Contracts\Caller;
use BeatSwitch\Lock\Contracts\Driver;
use BeatSwitch\Lock\Contracts\Permission;
use BeatSwitch\Lock\Permissions\Privilege;
use BeatSwitch\Lock\Permissions\Restriction;

class Lock
{
    /**
     * @var \BeatSwitch\Lock\Contracts\Caller
     */
    protected $caller;

    /**
     * @var \BeatSwitch\Lock\Contracts\Driver
     */
    protected $driver;

    /**
     * Action aliases
     *
     * @var \BeatSwitch\Lock\ActionAlias[]
     */
    protected $aliases = [];

    /**
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @param \BeatSwitch\Lock\Contracts\Driver $driver
     */
    public function __construct(Caller $caller, Driver $driver)
    {
        $this->caller = $caller;
        $this->driver = $driver;
    }

    /**
     * Determine if one or more actions are allowed
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    public function can($action, $resource = null, $resourceId = null)
    {
        $actions = (array) $action;

        foreach ($actions as $action) {
            if ($aliases = $this->getAliasesForAction($action)) {
                if ($this->can($aliases, $resource, $resourceId)) {
                    return true;
                }
            }

            if (! $this->isAllowed($action, $resource, $resourceId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if an action isn't allowed
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    public function cannot($action, $resource = null, $resourceId = null)
    {
        return ! $this->can($action, $resource, $resourceId);
    }

    /**
     * Adds a permission for a caller
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     */
    public function allow($action, $resource = null, $resourceId = null)
    {
        $actions = (array) $action;

        foreach ($actions as $action) {
            $restriction = new Restriction($action, $resource, $resourceId);

            if ($this->hasPermission($restriction)) {
                $this->removePermission($restriction);
            }

            $this->storePermission(new Privilege($action, $resource, $resourceId));
        }
    }

    /**
     * Removes a permission from a caller
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     */
    public function deny($action, $resource = null, $resourceId = null)
    {
        $actions = (array) $action;

        foreach ($actions as $action) {
            $privilege = new Privilege($action, $resource, $resourceId);

            if ($this->hasPermission($privilege)) {
                $this->removePermission($privilege);
            }

            $this->storePermission(new Restriction($action, $resource, $resourceId));
        }
    }

    /**
     * Change the value for a permission
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     */
    public function toggle($action, $resource = null, $resourceId = null)
    {
        if ($this->can($action, $resource, $resourceId)) {
            $this->deny($action, $resource, $resourceId);
        } else {
            $this->allow($action, $resource, $resourceId);
        }
    }

    /**
     * Register an alias to group certain actions
     *
     * @param string $name
     * @param string|array $actions
     */
    public function alias($name, $actions)
    {
        $this->aliases[$name] = new Actionalias($name, $actions);
    }

    /**
     * Determine if an action is allowed
     *
     * @param string $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    protected function isAllowed($action, $resource, $resourceId)
    {
        $permissions = $this->getPermissions();

        // Search for restrictions in the permissions. We'll do this first
        // because restrictions should override any privileges.
        foreach ($permissions as $permission) {
            // Check if the restriction is valid.
            if (
                $permission instanceof Restriction &&
                $permission->matchesPermission(new Restriction($action, $resource, $resourceId))
                && ! $permission->isAllowed($action, $resource, $resourceId)
            ) {
                // If we've found a matching restriction, set the flag to false.
                return false;
            }
        }

        // Search for privileges in the permissions.
        foreach ($permissions as $permission) {
            // Check if the privilege is valid.
            if ($permission instanceof Privilege && $permission->isAllowed($action, $resource, $resourceId)) {
                // If we've found a valid privilege, set the flag to true.
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the permissions for the current caller
     *
     * @return \BeatSwitch\Lock\Contracts\Permission[]
     */
    protected function getPermissions()
    {
        return $this->hasAnExistingCaller() ? $this->driver->getPermissions($this->caller) : [];
    }

    /**
     * Stores a permission into the driver
     *
     * @param \BeatSwitch\Lock\Contracts\Permission $permission
     */
    protected function storePermission(Permission $permission)
    {
        // It only makes sense to store a permission for a caller which exists.
        // Also don't re-store the permission if it already exists.
        if ($this->hasAnExistingCaller() && ! $this->hasPermission($permission)) {
            $this->driver->storePermission($this->caller, $permission);
        }
    }

    /**
     * Removes a permission from the driver
     *
     * @param \BeatSwitch\Lock\Contracts\Permission $permission
     */
    protected function removePermission(Permission $permission)
    {
        // It only makes sense to remove a permission for a caller which exists.
        if ($this->hasAnExistingCaller()) {
            $this->driver->removePermission($this->caller, $permission);
        }
    }

    /**
     * Checks if a caller has a specific permission
     *
     * @param \BeatSwitch\Lock\Contracts\Permission $permission
     * @return bool
     */
    protected function hasPermission(Permission $permission)
    {
        // It only makes sense to check a permission for a caller which exists.
        if ($this->hasAnExistingCaller()) {
            return $this->driver->hasPermission($this->caller, $permission);
        }

        return false;
    }

    /**
     * Returns the identifier for the caller
     *
     * @return bool
     */
    protected function hasAnExistingCaller()
    {
        return $this->caller->getCallerType() !== null && $this->caller->getCallerId() !== null;
    }

    /**
     * Returns all aliases which contain the given action
     *
     * @param string $action
     * @return array
     */
    public function getAliasesForAction($action)
    {
        $actions = [];

        foreach ($this->aliases as $aliasName => $alias) {
            if ($alias->hasAction($action)) {
                $actions[] = $aliasName;
            }
        }

        return $actions;
    }

    /**
     * The current caller for this Acl object
     *
     * @return \BeatSwitch\Lock\Contracts\Caller
     */
    public function getCaller()
    {
        return $this->caller;
    }

    /**
     * The current driver for this Acl object
     *
     * @return \BeatSwitch\Lock\Contracts\Driver
     */
    public function getDriver()
    {
        return $this->driver;
    }
}
