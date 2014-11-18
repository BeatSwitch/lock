<?php
namespace BeatSwitch\Lock;

use BeatSwitch\Lock\Contracts\Caller;
use BeatSwitch\Lock\Contracts\Driver;

class LockFactory
{
    /**
     * Creates a new Lock instance from a caller and a driver
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @param \BeatSwitch\Lock\Contracts\Driver $driver
     * @return \BeatSwitch\Lock\Lock
     */
    public static function make(Caller $caller, Driver $driver)
    {
        return new Lock($caller, $driver);
    }
}
