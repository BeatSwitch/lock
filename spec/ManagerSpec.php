<?php
namespace spec\BeatSwitch\Lock;

use BeatSwitch\Lock\Contracts\Driver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use spec\BeatSwitch\Lock\Stubs\CallerStub;

class ManagerSpec extends ObjectBehavior
{
    function let(Driver $driver)
    {
        $this->beConstructedWith($driver);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('BeatSwitch\Lock\Manager');
    }

    function it_can_instantiate_a_new_lock_instance_for_a_caller()
    {
        $this->caller(new CallerStub('users', 1))->shouldBeAnInstanceOf('BeatSwitch\Lock\Lock');
    }
}
