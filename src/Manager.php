<?php
namespace BeatSwitch\Lock;

use BeatSwitch\Lock\Contracts\Caller;
use BeatSwitch\Lock\Contracts\Driver;

class Manager
{
    /**
     * @var \BeatSwitch\Lock\Contracts\Driver
     */
    protected $driver;

    /**
     * @param \BeatSwitch\Lock\Contracts\Driver $driver
     */
    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Creates a new Lock instance for the given caller
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @return \BeatSwitch\Lock\Lock
     */
    public function caller(Caller $caller)
    {
        return new Lock($caller, $this->driver);
    }
}
