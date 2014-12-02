<?php
namespace tests\BeatSwitch\Lock;

use BeatSwitch\Lock\Drivers\ArrayDriver;

class ArrayDriverTest extends LockTestCase
{
    public function setUp()
    {
        $this->driver = new ArrayDriver();

        parent::setUp();
    }
}
