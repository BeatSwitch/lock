<?php
namespace tests\BeatSwitch\Lock\Drivers;

use BeatSwitch\Lock\Drivers\ArrayDriver;
use tests\BeatSwitch\Lock\LockTestCase;

class ArrayDriverTest extends LockTestCase
{
    public function setUp()
    {
        $this->driver = new ArrayDriver();

        parent::setUp();
    }
}
