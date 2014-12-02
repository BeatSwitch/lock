<?php
namespace BeatSwitch\Lock;

use BeatSwitch\Lock\Callers\Caller;
use BeatSwitch\Lock\Drivers\Driver;

class LockFactory
{
    /**
     * Creates a new Lock instance from a caller and a driver
     *
     * @param \BeatSwitch\Lock\Callers\Caller $caller
     * @param \BeatSwitch\Lock\Drivers\Driver $driver
     * @return \BeatSwitch\Lock\Lock
     */
    public static function make(Caller $caller, Driver $driver)
    {
        return new Lock($caller, $driver);
    }
}
