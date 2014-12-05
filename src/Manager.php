<?php
namespace BeatSwitch\Lock;

use BeatSwitch\Lock\Callers\Caller;
use BeatSwitch\Lock\Drivers\Driver;
use BeatSwitch\Lock\Roles\Role;
use BeatSwitch\Lock\Roles\SimpleRole;

class Manager
{
    /**
     * @var \BeatSwitch\Lock\Drivers\Driver
     */
    protected $driver;

    /**
     * @var \BeatSwitch\Lock\ActionAlias[]
     */
    protected $aliases = [];

    /**
     * @var \BeatSwitch\Lock\Roles\Role[]
     */
    protected $roles = [];

    /**
     * @param \BeatSwitch\Lock\Drivers\Driver $driver
     */
    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Creates a new Lock instance for the given caller
     *
     * @param \BeatSwitch\Lock\Callers\Caller $caller
     * @return \BeatSwitch\Lock\Callers\CallerLock
     */
    public function caller(Caller $caller)
    {
        return LockFactory::makeCallerLock($caller, $this);
    }

    /**
     * Creates a new Lock instance for the given role
     *
     * @param \BeatSwitch\Lock\Roles\Role|string $role
     * @return \BeatSwitch\Lock\Roles\RoleLock
     */
    public function role($role)
    {
        return LockFactory::makeRoleLock($this->convertRoleToObject($role), $this);
    }

    /**
     * Sets the lock instance on caller that implements the LockAware trait
     *
     * @param \BeatSwitch\Lock\Callers\Caller $caller
     * @return \BeatSwitch\Lock\Callers\Caller
     */
    public function makeCallerLockAware(Caller $caller)
    {
        $lock = $this->caller($caller);

        $caller->setLock($lock);

        return $caller;
    }

    /**
     * Sets the lock instance on role that implements the LockAware trait
     *
     * @param \BeatSwitch\Lock\Roles\Role|string $role
     * @return \BeatSwitch\Lock\Roles\Role
     */
    public function makeRoleLockAware($role)
    {
        $role = $this->convertRoleToObject($role);
        $lock = $this->role($role);

        $role->setLock($lock);

        return $role;
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
            $this->roles[$name] = new SimpleRole($name, $inherit);
        }
    }

    /**
     * Create a role value object if a non role object is passed
     *
     * @param string|\BeatSwitch\Lock\Roles\Role $role
     * @return \BeatSwitch\Lock\Roles\Role
     */
    public function convertRoleToObject($role)
    {
        return ! $role instanceof Role ? $this->findRole($role) : $role;
    }

    /**
     * Find a role in the roles array
     *
     * @param string $role
     * @return \BeatSwitch\Lock\Roles\Role
     */
    protected function findRole($role)
    {
        // Try to see if we have a role registered for the given
        // key so we can determine the role's inheritances.
        if (array_key_exists($role, $this->roles)) {
            return $this->roles[$role];
        }

        // If we couldn't find a registered role for the
        // given key, just return a new role object.
        return new SimpleRole($role);
    }

    /**
     * @return \BeatSwitch\Lock\Drivers\Driver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @return \BeatSwitch\Lock\ActionAlias[]
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * @return \BeatSwitch\Lock\Roles\Role[]
     */
    public function getRoles()
    {
        return $this->roles;
    }
}
