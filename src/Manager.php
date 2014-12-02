<?php
namespace BeatSwitch\Lock;

use BeatSwitch\Lock\Callers\Caller;
use BeatSwitch\Lock\Drivers\Driver;

class Manager
{
    /**
     * @var \BeatSwitch\Lock\Drivers\Driver
     */
    protected $driver;

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
     * @return \BeatSwitch\Lock\Lock
     */
    public function caller(Caller $caller)
    {
        return LockFactory::make($caller, $this->driver);
    }
}
