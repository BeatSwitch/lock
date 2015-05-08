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
     * @param string $resourceId
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

    public function canExplicitly($action, $resource = null, $resourceId = null) {
        $resource = $this->convertResourceToObject($resource, $resourceId);
        $permissions = $this->getPermissions();

        $hasPrivilege = false;

        foreach ($permissions as $permission) {
            if($permission instanceof Privilege && $permission->getAction() == $action && $resource->getResourceType() == $permission->getResourceType() && $resource->getResourceId() == $permission->getResourceId()) {
                $hasPrivilege = true;
            }
        }
        return $hasPrivilege;
    }

    public function cannotExplicitly($action, $resource = null, $resourceId = null) {
        $resource = $this->convertResourceToObject($resource, $resourceId);
        $permissions = $this->getPermissions();

        $hasRestriction = false;

        foreach ($permissions as $permission) {
            if($permission instanceof Restriction && $permission->getAction() == $action && $resource->getResourceType() == $permission->getResourceType() && $resource->getResourceId() == $permission->getResourceId()) {
                $hasRestriction = true;
            }
        }
        return $hasRestriction;
    }

    /**
     * Determine if an action isn't allowed
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param string $resourceId
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
     * @param string $resourceId
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
     * @param string $resourceId
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    public function deny($action, $resource = null, $resourceId = null, $conditions = [])
    {
        $actions = (array) $action;
        $resource = $this->convertResourceToObject($resource, $resourceId);
        $permissions = $this->getPermissions();

        foreach ($actions as $action) {
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

            $this->storePermission(new Restriction($action, $resource, $conditions));
        }
    }


    /**
     * Clear the subject permission/restriction to do something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param string $resourceId
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    public function clear($action, $resource = null, $resourceId = null, $conditions = [])
    {
        $actions = (array) $action;
        $resource = $this->convertResourceToObject($resource, $resourceId);
        $permissions = $this->getPermissions();
        foreach ($actions as $action) {
            $privilege = new Privilege($action, $resource);
            $this->removePermission($privilege);
            $restriction = new Restriction($action, $resource);
            $this->removePermission($restriction);
        }
    }


    /**
     * Change the value for a permission
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param string $resourceId
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
    public function allowed($actions, $resourceType)
    {
       
        // rules :
        // (1) a permission on caller level is always winner against a permission on role level
        // (2) a privilege is winner against a restriction between two different roles inherited by a caller
        // (1) > (2)
        
        // example : 
        // Peter inherits Editor role & Publisher role (R2 = Resource Id 2)
        // Peter is allowed to R2 and Editor is denied to R2 => Peter can R2
        // Peter is denied to R2 and Editor is allowed to R2 => Peter cannot R2
        // Peter has not explicit permission on R2, but Editor is allowed to R2 and Publisher is denied to R2 => Peter can R2

        /* !!! only works with one level of heritence of role on caller */

        // if actions is more than one action, it will return resource id with Privilege for ALL action

        $resourceType = $resourceType instanceof Resource ? $resourceType->getResourceType() : $resourceType;
        $actions = (array) $actions;
        $allowed = [];
        $allowedOnCallerLevel = [];
        $deniedOnCallerLevel = [];
        $allowedOnRoleLevel = [];

        foreach($actions as $action) {
            
            $allowed[$action] = [];
            $allowedOnCallerLevel[$action] = [];
            $deniedOnCallerLevel[$action] = [];
            $allowedOnRoleLevel[$action] = [];

            // browse permission of a role / caller
            foreach($this->getPermissions() as $permission){
                if($permission->getResourceType() != $resourceType || $permission->getAction() != $action) {
                    continue;
                }

                if($permission instanceof Restriction) {
                    if(!in_array($permission->getResourceId(), $deniedOnCallerLevel[$action])) {
                        $deniedOnCallerLevel[$action][] = $permission->getResourceId();
                    }
                } elseif($permission instanceof Privilege) {
                    if(!in_array($permission->getResourceId(), $allowedOnCallerLevel[$action])) {
                        $allowedOnCallerLevel[$action][] = $permission->getResourceId();
                    }
                } else {
                    throw new \Exception("Unrecognize permission", 1);   
                }
            }

            // browse the permission of roles inherited by the caller
            if(method_exists($this, 'getLockInstancesForCallerRoles')) {
                foreach ($this->getLockInstancesForCallerRoles() as $role) {
                    foreach ($role->getPermissions() as $permission) {
                        if($permission->getResourceType() != $resourceType || $permission->getAction() != $action) {
                            continue;
                        }

                        if($permission instanceof Privilege) {
                            if(!in_array($permission->getResourceId(), $allowedOnRoleLevel[$action])) {
                                $allowedOnRoleLevel[$action][] = $permission->getResourceId();
                            }
                        } 
                    }
                }
                
                // we keep only the Privilege on role that are not a Restriction by the children(caller level)
                $allowedOnRoleLevel[$action] = array_diff($allowedOnRoleLevel[$action], $deniedOnCallerLevel[$action]);
            }

            // we combine Privilege on role level with Privilege on caller level
            $allowed[$action] = array_unique(array_merge($allowedOnRoleLevel[$action], $allowedOnCallerLevel[$action]));
        }

        if(empty($actions)) {
            // no action => nothing to return
            return [];
        } elseif(count($actions) == 1) {
            // only one action = we return the allowed ressource id
            return $allowed[$actions[0]];
        } else {
            // we return ressource id present in all actions
            $intersect = call_user_func_array('array_intersect', $allowed);
            return array_values($intersect);
        }
    }

    /**
     * Returns the denied ids which match the given action and resource type
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resourceType
     * @return array
     */
    public function denied($actions, $resourceType)
    {

        // if actions is more than one action, it will return resource id with Privilege for AT LEAST ONE action

        $resourceType = $resourceType instanceof Resource ? $resourceType->getResourceType() : $resourceType;
        $actions = (array) $actions;
        $denied = [];
        $allowedOnCallerLevel = [];
        $deniedOnCallerLevel = [];
        $allowedOnRoleLevel = [];
        $deniedOnRoleLevel = [];

        foreach($actions as $action) {
            $denied[$action] = [];
            $allowedOnCallerLevel[$action] = [];
            $deniedOnCallerLevel[$action] = [];
            $deniedOnRoleLevel[$action] = [];

            // browse permission of a role / caller
            foreach($this->getPermissions() as $permission){
                if($permission->getResourceType() != $resourceType || $permission->getAction() != $action) {
                    continue;
                }

                if($permission instanceof Restriction) {
                    if(!in_array($permission->getResourceId(), $deniedOnCallerLevel[$action])) {
                        $deniedOnCallerLevel[$action][] = $permission->getResourceId();
                    }
                } elseif($permission instanceof Privilege) {
                    if(!in_array($permission->getResourceId(), $allowedOnCallerLevel[$action])) {
                        $allowedOnCallerLevel[$action][] = $permission->getResourceId();
                    }
                } else {
                    throw new \Exception("Unrecognize permission", 1);   
                }
            }

            // browse the permission of roles inherited by the caller
            if(method_exists($this, 'getLockInstancesForCallerRoles')) {
                foreach ($this->getLockInstancesForCallerRoles() as $role) {
                    foreach ($role->getPermissions() as $permission) {
                        if($permission->getResourceType() != $resourceType || $permission->getAction() != $action) {
                            continue;
                        }

                        if($permission instanceof Privilege) {
                            if(!in_array($permission->getResourceId(), $allowedOnRoleLevel[$action])) {
                                $allowedOnRoleLevel[$action][] = $permission->getResourceId(); 
                            }

                            // remove any Restriction set on another role (Rule 2)
                            if(in_array($permission->getResourceId(), $deniedOnRoleLevel[$action])) {
                                $deniedOnRoleLevel[$action] = array_diff($deniedOnRoleLevel[$action],[$permission->getResourceId()]);
                            }
                        }

                        if($permission instanceof Restriction) {
                            if(!in_array($permission->getResourceId(), $deniedOnRoleLevel[$action]) && !in_array($permission->getResourceId(), $allowedOnRoleLevel[$action])) {
                                $deniedOnRoleLevel[$action][] = $permission->getResourceId();
                            }
                        } 
                    }
                }

                // we keep only the Restriction on role that are not a Allowed by the children(caller level)
                $deniedOnRoleLevel[$action] = array_diff($deniedOnRoleLevel[$action], $allowedOnCallerLevel[$action]);
            }

            // we combine Restriction on role level with Restriction on caller level
            $denied[$action] = array_unique(array_merge($deniedOnRoleLevel[$action], $deniedOnCallerLevel[$action]));
        }

        if(empty($actions)) {
            // no action => nothing to return
            return [];
        } elseif(count($actions) == 1) {
            // only one action = we return the denied ressource id
            return $denied[$actions[0]];
        } else {
            // we return ressource id present in actions (not specially in all actions)
            $intersect = call_user_func_array('array_merge', $denied);
            return array_values($intersect);
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
     * @param string|null $resourceId
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
