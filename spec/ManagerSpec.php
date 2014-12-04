<?php
namespace spec\BeatSwitch\Lock;

use BeatSwitch\Lock\ActionAlias;
use BeatSwitch\Lock\Callers\SimpleCaller;
use BeatSwitch\Lock\Drivers\Driver;
use BeatSwitch\Lock\Roles\SimpleRole;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

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
        $this->getCallerLock(new SimpleCaller('users', 1))->shouldBeAnInstanceOf('BeatSwitch\Lock\Callers\CallerLock');
    }

    function it_can_instantiate_a_new_lock_instance_for_a_role()
    {
        $this->setRole('editor');

        $this->getRoleLock('editor')->shouldBeAnInstanceOf('BeatSwitch\Lock\Roles\RoleLock');
    }

    function it_can_set_action_aliases()
    {
        $this->alias('manage', ['create', 'read', 'update', 'delete']);

        $this->getAliases()->shouldHaveCount(1);
    }

    function it_can_set_roles()
    {
        $this->setRole('user');
        $this->setRole(['editor', 'admin'], 'user');

        $this->getRoles()->shouldHaveCount(3);
    }
}
