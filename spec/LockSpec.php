<?php
namespace spec\BeatSwitch\Lock;

use BeatSwitch\Lock\Drivers\ArrayDriver;
use BeatSwitch\Lock\Callers\NullCaller;
use PhpSpec\ObjectBehavior;
use spec\BeatSwitch\Lock\Stubs\CallerStub;

class LockSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(new CallerStub('users', 1), new ArrayDriver());
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('BeatSwitch\Lock\Lock');
    }

    function it_can_handle_a_single_action_permission()
    {
        $this->allow('edit');

        $this->can('edit')->shouldReturn(true);
        $this->cannot('edit')->shouldReturn(false);
        $this->can('edit', 'users')->shouldReturn(true);
        $this->can('edit', 'users', 1)->shouldReturn(true);
    }

    function it_can_handle_resource_type_permissions()
    {
        $this->allow('edit', 'users');

        $this->can('edit')->shouldReturn(false);
        $this->cannot('edit')->shouldReturn(true);
        $this->can('edit', 'users')->shouldReturn(true);
        $this->can('edit', 'users', 1)->shouldReturn(true);
    }

    function it_can_handle_specific_resource_permissions()
    {
        $this->allow('edit', 'users', 5);

        $this->can('edit')->shouldReturn(false);
        $this->cannot('edit')->shouldReturn(true);
        $this->can('edit', 'users')->shouldReturn(false);
        $this->can('edit', 'users', 1)->shouldReturn(false);
        $this->can('edit', 'users', 5)->shouldReturn(true);
    }

    function it_can_handle_all_permissions()
    {
        $this->allow('all');

        $this->can('edit')->shouldReturn(true);
        $this->cannot('edit')->shouldReturn(false);
        $this->can('edit', 'users')->shouldReturn(true);
        $this->can('edit', 'users', 1)->shouldReturn(true);
    }

    function it_can_handle_all_permissions_for_a_resource()
    {
        $this->allow('all', 'users');

        $this->can('edit')->shouldReturn(false);
        $this->cannot('edit')->shouldReturn(true);
        $this->can('edit', 'users')->shouldReturn(true);
        $this->can('edit', 'users', 1)->shouldReturn(true);
        $this->can('edit', 'events')->shouldReturn(false);
    }

    function it_can_handle_all_permissions_for_a_specific_resource()
    {
        $this->allow('all', 'users', 1);

        $this->can('edit')->shouldReturn(false);
        $this->cannot('edit')->shouldReturn(true);
        $this->can('edit', 'users')->shouldReturn(false);
        $this->can('edit', 'users', 1)->shouldReturn(true);
        $this->can('edit', 'events', 1)->shouldReturn(false);
    }

    function it_can_toggle_permissions()
    {
        $this->toggle('edit', 'users');
        $this->can('edit', 'users')->shouldReturn(true);
        $this->toggle('edit', 'users');
        $this->can('edit', 'users')->shouldReturn(false);
    }

    function it_always_returns_false_when_it_is_a_nullcaller()
    {
        $this->beConstructedWith(new NullCaller(), new ArrayDriver());

        $this->cannot('create')->shouldReturn(true);
        $this->cannot('edit', 'users', 1)->shouldReturn(true);
        $this->cannot('delete', 'events')->shouldReturn(true);
    }
}
