<?php
namespace BeatSwitch\Lock;

use BeatSwitch\Lock\Permissions\Permission;
use BeatSwitch\Lock\Resources\Resource;
use BeatSwitch\Lock\Resources\SimpleResource;
use BeatSwitch\Lock\Permissions\Privilege;
use BeatSwitch\Lock\Permissions\Restriction;

abstract class Lock
{
    /**
     * @var \BeatSwitch\Lock\Manager
     */
    protected $manager;

    /**
     * Determine if one or more actions are allowed
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    public function can($action, $resource = null, $resourceId = null)
    {
        $actions = (array) $action;
        $resource = $this->convertResourceToObject($resource, $resourceId);
        $permissions = $this->getPermissions();

        foreach ($actions as $action) {
            if ($aliases = $this->getAliasesForAction($action)) {
                if ($this->can($aliases, $resource) && $this->resolveRestrictions($permissions, $action, $resource)) {
                    return true;
                }
            }

            if (! $this->resolvePermissions($action, $resource)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if an action isn't allowed
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    public function cannot($action, $resource = null, $resourceId = null)
    {
        return ! $this->can($action, $resource, $resourceId);
    }

    /**
     * Give the subject permission to do something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param int $resourceId
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    public function allow($action, $resource = null, $resourceId = null, $conditions = [])
    {
        $actions = (array) $action;
        $resource = $this->convertResourceToObject($resource, $resourceId);
        $permissions = $this->getPermissions();

        foreach ($actions as $action) {
            foreach ($permissions as $key => $permission) {
                if ($permission instanceof Restriction && ! $permission->isAllowed($this, $action, $resource)) {
                    $this->removePermission($permission);
                    unset($permissions[$key]);
                }
            }

            // We'll need to clear any restrictions above
            $restriction = new Restriction($action, $resource);

            if ($this->hasPermission($restriction)) {
                $this->removePermission($restriction);
            }

            $this->storePermission(new Privilege($action, $resource, $conditions));
        }
    }

    /**
     * Deny the subject from doing something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param int $resourceId
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    public function deny($action, $resource = null, $resourceId = null, $conditions = [])
    {
        $actions = (array) $action;
        $resource = $this->convertResourceToObject($resource, $resourceId);
        $permissions = $this->getPermissions();

        foreach ($actions as $action) {
            $this->clearPermission($action, $resource, $permissions);

            $this->storePermission(new Restriction($action, $resource, $conditions));
        }
    }

    /**
     * Change the value for a permission
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
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
     * Returns the allowed ids which match the given action and resource type
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resourceType
     * @return array
     */
    public function allowed($action, $resourceType)
    {
        $resourceType = $resourceType instanceof Resource ? $resourceType->getResourceType() : $resourceType;

        // Get all the ids from privileges which match the given resource type.
        $ids = array_unique(array_map(function (Permission $permission) {
            return $permission->getResourceId();
        }, array_filter($this->getPermissions(), function (Permission $permission) use ($resourceType) {
            return $permission instanceof Privilege && $permission->getResourceType() === $resourceType;
        })));

        return array_values(array_filter($ids, function ($id) use ($action, $resourceType) {
            return $this->can($action, $resourceType, $id);
        }));
    }

    /**
     * Returns the denied ids which match the given action and resource type
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resourceType
     * @return array
     */
    public function denied($action, $resourceType)
    {
        $resourceType = $resourceType instanceof Resource ? $resourceType->getResourceType() : $resourceType;

        // Get all the ids from restrictions which match the given resource type.
        $ids = array_unique(array_map(function (Permission $permission) {
            return $permission->getResourceId();
        }, array_filter($this->getPermissions(), function (Permission $permission) use ($resourceType) {
            return $permission instanceof Restriction && $permission->getResourceType() === $resourceType;
        })));

        return array_values(array_filter($ids, function ($id) use ($action, $resourceType) {
            return $this->cannot($action, $resourceType, $id);
        }));
    }

    /**
     * Clear a given permission on a subject
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param int $resourceId
     */
    public function clear($action = null, $resource = null, $resourceId = null)
    {
        $actions = (array) $action;
        $resourceObject = $this->convertResourceToObject($resource, $resourceId);
        $permissions = $this->getPermissions();

        if ($action === null && $resource === null) {
            // Clear every permission for this lock instance.
            foreach ($permissions as $permission) {
                $this->removePermission($permission);
            }
        } elseif ($action === null && $resource !== null) {
            // Clear all permissions for a given resource.
            /** @todo Needs to be implemented */
        } else {
            // Clear every permission for the given actions.
            foreach ($actions as $action) {
                $this->clearPermission($action, $resourceObject, $permissions);
            }
        }
    }

    /**
     * @param string $action
     * @param \BeatSwitch\Lock\Resources\Resource $resource
     * @param \BeatSwitch\Lock\Permissions\Permission[] $permissions
     */
    private function clearPermission($action, Resource $resource, array $permissions)
    {
        foreach ($permissions as $key => $permission) {
            if ($permission instanceof Privilege && $permission->isAllowed($this, $action, $resource)) {
                $this->removePermission($permission);
                unset($permissions[$key]);
            }
        }

        $privilege = new Privilege($action, $resource);

        if ($this->hasPermission($privilege)) {
            $this->removePermission($privilege);
        }
    }

    /**
     * Determine if an action is allowed
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Resources\Resource $resource
     * @return bool
     */
    abstract protected function resolvePermissions($action, Resource $resource);

    /**
     * Check if the given restrictions prevent the given action and resource to pass
     *
     * @param \BeatSwitch\Lock\Permissions\Permission[] $permissions
     * @param string $action
     * @param \BeatSwitch\Lock\Resources\Resource $resource
     * @return bool
     */
    protected function resolveRestrictions($permissions, $action, Resource $resource)
    {
        foreach ($permissions as $permission) {
            // If we've found a matching restriction, return false.
            if ($permission instanceof Restriction && ! $permission->isAllowed($this, $action, $resource)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the given privileges allow the given action and resource to pass
     *
     * @param \BeatSwitch\Lock\Permissions\Permission[] $permissions
     * @param string $action
     * @param \BeatSwitch\Lock\Resources\Resource $resource
     * @return bool
     */
    protected function resolvePrivileges($permissions, $action, Resource $resource)
    {
        // Search for privileges in the permissions.
        foreach ($permissions as $permission) {
            // If we've found a valid privilege, return true.
            if ($permission instanceof Privilege && $permission->isAllowed($this, $action, $resource)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the permissions for the current subject
     *
     * @return \BeatSwitch\Lock\Permissions\Permission[]
     */
    abstract protected function getPermissions();

    /**
     * Stores a permission into the driver
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     */
    abstract protected function storePermission(Permission $permission);

    /**
     * Removes a permission from the driver
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     */
    abstract protected function removePermission(Permission $permission);

    /**
     * Checks if the subject has a specific permission
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     * @return bool
     */
    abstract protected function hasPermission(Permission $permission);

    /**
     * Returns all aliases which contain the given action
     *
     * @param string $action
     * @return array
     */
    protected function getAliasesForAction($action)
    {
        $actions = [];

        foreach ($this->manager->getAliases() as $aliasName => $alias) {
            if ($alias->hasAction($action)) {
                $actions[] = $aliasName;
            }
        }

        return $actions;
    }

    /**
     * Create a resource value object if a non resource object is passed
     *
     * @param string|\BeatSwitch\Lock\Resources\Resource|null $resource
     * @param int|null $resourceId
     * @return \BeatSwitch\Lock\Resources\Resource
     */
    protected function convertResourceToObject($resource, $resourceId = null)
    {
        return ! $resource instanceof Resource ? new SimpleResource($resource, $resourceId) : $resource;
    }

    /**
     * Returns the current lock instant's subject
     *
     * @return object
     */
    abstract public function getSubject();

    /**
     * The current manager instance
     *
     * @return \BeatSwitch\Lock\Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * The current driver provided by the manager
     *
     * @return \BeatSwitch\Lock\Drivers\Driver
     */
    public function getDriver()
    {
        return $this->manager->getDriver();
    }
}
