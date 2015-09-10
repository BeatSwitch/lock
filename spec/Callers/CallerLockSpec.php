<?php
namespace spec\BeatSwitch\Lock\Callers;

// Import stubs
require_once __DIR__ . '/../../stubs/FalseConditionStub.php';
require_once __DIR__ . '/../../stubs/TrueConditionStub.php';

use BeatSwitch\Lock\Drivers\ArrayDriver;
use BeatSwitch\Lock\Callers\SimpleCaller;
use BeatSwitch\Lock\Manager;
use PhpSpec\ObjectBehavior;
use stubs\BeatSwitch\Lock\FalseConditionStub;
use stubs\BeatSwitch\Lock\TrueConditionStub;

class CallerLockSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(new SimpleCaller('users', 1), new Manager(new ArrayDriver()));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('BeatSwitch\Lock\Callers\CallerLock');
    }

    function it_set_and_check_permissions()
    {
        $this->allow(['create', 'edit']);

        $this->can('create')->shouldReturn(true);
        $this->can('edit')->shouldReturn(true);
    }

    function it_set_and_inverse_check_permissions()
    {
        $this->allow(['create', 'edit']);

        $this->cannot('update')->shouldReturn(true);
    }

    function it_can_deny_permissions()
    {
        $this->allow('edit', 'users');
        $this->deny('edit');

        $this->can('edit')->shouldReturn(false);
    }

    function it_can_clear_privileges()
    {
        $this->allow('edit');
        $this->clear('edit');

        $this->can('edit')->shouldReturn(false);
    }

    function it_can_clear_restrictions()
    {
        $this->deny('edit');
        $this->clear('edit');

        $this->can('edit')->shouldReturn(false);
    }

    function it_can_handle_a_wildcard()
    {
        $this->allow('all');

        $this->can('edit')->shouldReturn(true);
        $this->cannot('edit')->shouldReturn(false);
        $this->can('edit', 'users')->shouldReturn(true);
        $this->can('edit', 'users', 1)->shouldReturn(true);
    }

    function it_can_toggle_permissions()
    {
        $this->toggle('edit', 'users');
        $this->can('edit', 'users')->shouldReturn(true);

        $this->toggle('edit', 'users');
        $this->can('edit', 'users')->shouldReturn(false);

        $this->toggle(['create', 'edit'], 'users');
        $this->can(['create', 'edit'], 'users')->shouldReturn(true);
    }

    function it_can_work_with_permission_conditions()
    {
        $this->allow('create', 'posts', null, new TrueConditionStub());
        $this->allow('create', 'pages', null, new FalseConditionStub());

        $this->can('create', 'posts')->shouldReturn(true);
        $this->can('create', 'pages')->shouldReturn(false);
    }
}
