<?php
namespace tests\BeatSwitch\Lock\Drivers;

use BeatSwitch\Lock\Drivers\ArrayDriver;
use BeatSwitch\Lock\Tests\StaticDriverTestCase;

class ArrayDriverTest extends StaticDriverTestCase
{
    public function setUp()
    {
        $this->driver = new ArrayDriver();

        parent::setUp();
    }
}
