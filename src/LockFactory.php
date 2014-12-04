<?php
namespace BeatSwitch\Lock;

use BeatSwitch\Lock\Callers\Caller;
use BeatSwitch\Lock\Callers\CallerLock;
use BeatSwitch\Lock\Roles\Role;
use BeatSwitch\Lock\Roles\RoleLock;

class LockFactory
{
    /**
     * Creates a new Lock instance from a caller and a driver
     *
     * @param \BeatSwitch\Lock\Callers\Caller $caller
     * @param \BeatSwitch\Lock\Manager $manager
     * @return \BeatSwitch\Lock\Lock
     */
    public static function makeCallerLock(Caller $caller, Manager $manager)
    {
        return new CallerLock($caller, $manager);
    }

    /**
     * Creates a new Lock instance from a caller and a driver
     *
     * @param \BeatSwitch\Lock\Roles\Role $role
     * @param \BeatSwitch\Lock\Manager $manager
     * @return \BeatSwitch\Lock\Lock
     */
    public static function makeRoleLock(Role $role, Manager $manager)
    {
        return new RoleLock($role, $manager);
    }
}
