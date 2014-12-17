<?php
namespace tests\BeatSwitch\Lock\Drivers;

use BeatSwitch\Lock\Drivers\ArrayDriver;

class ArrayDriverTest extends StaticDriverTestCase
{
    public function setUp()
    {
        $this->driver = new ArrayDriver();

        parent::setUp();
    }
}
