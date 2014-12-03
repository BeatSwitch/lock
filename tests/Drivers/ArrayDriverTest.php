<?php
namespace tests\BeatSwitch\Lock\Drivers;

use BeatSwitch\Lock\Drivers\ArrayDriver;
use tests\BeatSwitch\Lock\PersistentDriverTestCase;

class ArrayDriverTest extends StaticDriverTestCase
{
    public function setUp()
    {
        $this->driver = new ArrayDriver();

        parent::setUp();
    }
}
