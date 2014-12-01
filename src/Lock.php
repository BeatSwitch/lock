<?php
namespace BeatSwitch\Lock;

use BeatSwitch\Lock\Contracts\Caller;
use BeatSwitch\Lock\Contracts\Driver;
use BeatSwitch\Lock\Contracts\Permission;
use BeatSwitch\Lock\Contracts\Resource as ResourceContract;
use BeatSwitch\Lock\Contracts\Role as RoleContract;
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
     * @var \BeatSwitch\Lock\Contracts\Role[]
     */
    protected $roles = [];

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
        $resource = $this->getResourceObject($resource, $resourceId);

        foreach ($actions as $action) {
            if ($aliases = $this->getAliasesForAction($action)) {
                if ($this->can($aliases, $resource)) {
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
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    public function cannot($action, $resource = null, $resourceId = null)
    {
        return ! $this->can($action, $resource, $resourceId);
    }

    /**
     * Give a caller permission to do something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @param \BeatSwitch\Lock\Contracts\Condition[] $conditions
     */
    public function allow($action, $resource = null, $resourceId = null, array $conditions = array())
    {
        $actions = (array) $action;
        $resource = $this->getResourceObject($resource, $resourceId);
        $permissions = $this->getPermissions();

        foreach ($actions as $action) {
            foreach ($permissions as $key => $permission) {
                if ($permission instanceof Restriction && ! $permission->isAllowed($action, $resource)) {
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
     * Deny a caller from doing something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @param \BeatSwitch\Lock\Contracts\Condition[] $conditions
     */
    public function deny($action, $resource = null, $resourceId = null, array $conditions = array())
    {
        $actions = (array) $action;
        $resource = $this->getResourceObject($resource, $resourceId);
        $permissions = $this->getPermissions();

        foreach ($actions as $action) {
            foreach ($permissions as $key => $permission) {
                if ($permission instanceof Privilege && $permission->isAllowed($action, $resource)) {
                    $this->removePermission($permission);
                    unset($permissions[$key]);
                }
            }

            $privilege = new Privilege($action, $resource);

            if ($this->hasPermission($privilege)) {
                $this->removePermission($privilege);
            }

            $this->storePermission(new Restriction($action, $resource, $conditions));
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
        $this->aliases[$name] = new ActionAlias($name, $actions);
    }

    /**
     * Add one role to the lock instance
     *
     * @param string|array $names
     * @param string $inherit
     */
    public function setRole($names, $inherit = null)
    {
        foreach ((array) $names as $name) {
            $this->roles[$name] = new Role($name, $inherit);
        }
    }

    /**
     * Give a role permission to do something
     *
     * @param string|array|\BeatSwitch\Lock\Contracts\Role $role
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @param \BeatSwitch\Lock\Contracts\Condition[]
     */
    public function allowRole($role, $action, $resource = null, $resourceId = null, array $conditions = array())
    {
        $roles = (array) $role;
        $actions = (array) $action;
        $resource = $this->getResourceObject($resource, $resourceId);

        foreach ($roles as $role) {
            if ($role = $this->getRoleObject($role)) {
                $permissions = $this->getRolePermissions($role);

                foreach ($actions as $action) {
                    foreach ($permissions as $key => $permission) {
                        if ($permission instanceof Restriction && !$permission->isAllowed($action, $resource)) {
                            $this->removeRolePermission($role, $permission);
                            unset($permissions[$key]);
                        }
                    }

                    $restriction = new Restriction($action, $resource);

                    if ($this->hasRolePermission($role, $restriction)) {
                        $this->removeRolePermission($role, $restriction);
                    }

                    $this->storeRolePermission($role, new Privilege($action, $resource, $conditions));
                }
            }
        }
    }

    /**
     * Deny a role from doing something
     *
     * @param string|array|\BeatSwitch\Lock\Contracts\Role $roles
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @param \BeatSwitch\Lock\Contracts\Condition[]
     */
    public function denyRole($roles, $action, $resource = null, $resourceId = null, array $conditions = array())
    {
        $roles = (array) $roles;
        $actions = (array) $action;
        $resource = $this->getResourceObject($resource, $resourceId);

        foreach ($roles as $role) {
            if ($role = $this->getRoleObject($role)) {
                $permissions = $this->getRolePermissions($role);

                foreach ($actions as $action) {
                    foreach ($permissions as $key => $permission) {
                        if ($permission instanceof Privilege && $permission->isAllowed($action, $resource)) {
                            $this->removeRolePermission($role, $permission);
                            unset($permissions[$key]);
                        }
                    }

                    $privilege = new Privilege($action, $resource);

                    if ($this->hasRolePermission($role, $privilege)) {
                        $this->removeRolePermission($role, $privilege);
                    }

                    $this->storeRolePermission($role, new Restriction($action, $resource, $conditions));
                }
            }
        }
    }

    /**
     * Determine if an action is allowed
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Contracts\Resource $resource
     * @return bool
     */
    protected function resolvePermissions($action, ResourceContract $resource = null)
    {
        $permissions = $this->getPermissions();
        $rolePermissions = $this->getRolePermissionsForCaller();

        $callerRestrictionsResult = $this->resolveRestrictions($permissions, $action, $resource);

        // Search for restrictions in the permissions. We'll do this first
        // because restrictions should override any privileges.
        if (! $callerRestrictionsResult) {
            return false;
        }

        $rolesRestrictionsResult = $this->resolveRestrictions($rolePermissions, $action, $resource);
        $callerPrivilegesResult = $this->resolvePrivileges($permissions, $action, $resource);

        // If there are restrictions on the roles but caller specific privileges are set, allow this to pass.
        if (! $rolesRestrictionsResult && $callerPrivilegesResult) {
            return true;
        }

        $rolesPrivilegesResult = $this->resolvePrivileges($rolePermissions, $action, $resource);

        // If no restrictions are found, pass when a privilege is found on either the roles or caller.
        return $callerPrivilegesResult || $rolesPrivilegesResult;
    }

    /**
     * Check if the given restrictions prevent the given action and resource to pass
     *
     * @param \BeatSwitch\Lock\Contracts\Permission[] $permissions
     * @param string $action
     * @param \BeatSwitch\Lock\Contracts\Resource $resource
     * @return bool
     */
    protected function resolveRestrictions($permissions, $action, ResourceContract $resource)
    {
        foreach ($permissions as $permission) {
            // If we've found a matching restriction, return false.
            if ($permission instanceof Restriction && ! $permission->isAllowed($action, $resource)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the given privileges allow the given action and resource to pass
     *
     * @param \BeatSwitch\Lock\Contracts\Permission[] $permissions
     * @param string $action
     * @param \BeatSwitch\Lock\Contracts\Resource $resource
     * @return bool
     */
    protected function resolvePrivileges($permissions, $action, ResourceContract $resource)
    {
        // Search for privileges in the permissions.
        foreach ($permissions as $permission) {
            // If we've found a valid privilege, return true.
            if ($permission instanceof Privilege && $permission->isAllowed($action, $resource)) {
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
     * Returns the permissions for the current caller
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @return \BeatSwitch\Lock\Contracts\Permission[]
     */
    protected function getRolePermissions(RoleContract $role)
    {
        return $this->driver->getRolePermissions($role);
    }

    /**
     * Stores a permission into the driver
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @param \BeatSwitch\Lock\Contracts\Permission $permission
     */
    protected function storeRolePermission(RoleContract $role, Permission $permission)
    {
        // Don't re-store the permission if it already exists.
        if (! $this->hasRolePermission($role, $permission)) {
            $this->driver->storeRolePermission($role, $permission);
        }
    }

    /**
     * Removes a permission from the driver
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @param \BeatSwitch\Lock\Contracts\Permission $permission
     */
    protected function removeRolePermission(RoleContract $role, Permission $permission)
    {
        $this->driver->removeRolePermission($role, $permission);
    }

    /**
     * Checks if a caller has a specific permission
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @param \BeatSwitch\Lock\Contracts\Permission $permission
     * @return bool
     */
    protected function hasRolePermission(RoleContract $role, Permission $permission)
    {
        return $this->driver->hasRolePermission($role, $permission);
    }

    /**
     * Get the permissions for all the roles from the current caller
     *
     * @return \BeatSwitch\Lock\Contracts\Permission[]
     */
    protected function getRolePermissionsForCaller()
    {
        $roles = $this->caller->getCallerRoles();
        $permissions = [];

        foreach ($roles as $role) {
            if ($role = $this->getRoleObject($role)) {
                $permissions = array_merge($permissions, $this->getInheritedRolePermissions($role));
            }
        }

        return $permissions;
    }

    /**
     * Returns all the permissions for a role and their inherited roles
     *
     * @param \BeatSwitch\Lock\Contracts\Role $role
     * @return \BeatSwitch\Lock\Contracts\Permission[]
     */
    protected function getInheritedRolePermissions(RoleContract $role)
    {
        $permissions = $this->getRolePermissions($role);

        if ($inheritedRole = $role->getInheritedRole()) {
            $inheritedRole = $this->getRoleObject($inheritedRole);

            $permissions = array_merge($permissions, $this->getInheritedRolePermissions($inheritedRole));
        }

        return $permissions;
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
    protected function getAliasesForAction($action)
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
     * Create a resource value object if a non resource object is passed
     *
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int|null $resourceId
     * @return \BeatSwitch\Lock\Contracts\Resource
     */
    protected function getResourceObject($resource, $resourceId = null)
    {
        if (! $resource instanceof ResourceContract) {
            return new Resource($resource, $resourceId);
        }

        return $resource;
    }

    /**
     * Create a role value object if a non role object is passed
     *
     * @param string|\BeatSwitch\Lock\Contracts\Role $role
     * @return \BeatSwitch\Lock\Contracts\Role|null
     */
    protected function getRoleObject($role)
    {
        return ! $role instanceof RoleContract ? $this->findRole($role) : $role;
    }

    /**
     * Find a role in roles array
     *
     * @param string $role
     * @return \BeatSwitch\Lock\Contracts\Role|null
     */
    protected function findRole($role)
    {
        if (array_key_exists($role, $this->roles)) {
            return $this->roles[$role];
        }

        return null;
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
