<?php
namespace spec\BeatSwitch\Lock;

// Import stubs
require_once __DIR__ . '/../stubs/CallerStub.php';
require_once __DIR__ . '/../stubs/FalseConditionStub.php';
require_once __DIR__ . '/../stubs/TrueConditionStub.php';

use BeatSwitch\Lock\Drivers\ArrayDriver;
use BeatSwitch\Lock\Callers\NullCaller;
use PhpSpec\ObjectBehavior;
use stubs\BeatSwitch\Lock\CallerStub;
use stubs\BeatSwitch\Lock\FalseConditionStub;
use stubs\BeatSwitch\Lock\TrueConditionStub;

class LockSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(new CallerStub('users', 1, ['editor']), new ArrayDriver());
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('BeatSwitch\Lock\Lock');
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

    function it_can_set_action_aliases()
    {
        $this->alias('manage', ['create', 'read', 'update', 'delete']);
        $this->allow('manage', 'posts');

        $this->can('create')->shouldReturn(false);
        $this->can('manage')->shouldReturn(false);
        $this->can('manage', 'posts')->shouldReturn(true);
        $this->can('manage', 'posts', 1)->shouldReturn(true);
        $this->can('manage', 'events')->shouldReturn(false);
        $this->can('create', 'posts')->shouldReturn(true);
        $this->can(['read', 'update'], 'posts')->shouldReturn(true);
    }

    function it_can_work_with_roles()
    {
        $this->setRole('user');
        $this->setRole(['editor', 'admin'], 'user');

        $this->allowRole('user', 'create', 'posts');
        $this->allowRole(['editor', 'admin'], 'publish', 'posts');
        $this->allowRole('admin', 'delete', 'posts');

        // Our CallerStub has the editor role.
        $this->can(['create', 'publish'], 'posts')->shouldReturn(true);
        $this->can('delete', 'posts')->shouldReturn(false);
    }

    function it_can_work_with_permission_conditions()
    {
        $this->allow('create', 'posts', null, [new TrueConditionStub()]);
        $this->allow('create', 'pages', null, [new FalseConditionStub()]);

        $this->can('create', 'posts')->shouldReturn(true);
        $this->can('create', 'pages')->shouldReturn(false);
    }

    function it_always_returns_false_when_it_is_a_nullcaller()
    {
        $this->beConstructedWith(new NullCaller(), new ArrayDriver());

        $this->cannot('create')->shouldReturn(true);
        $this->cannot('edit', 'users', 1)->shouldReturn(true);
        $this->cannot('delete', 'events')->shouldReturn(true);
    }
}
