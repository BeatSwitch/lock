<?php
namespace spec\BeatSwitch\Lock;

// For some bizar reason it can find the CallerStub but not the rest of the stubs.
require __DIR__ . '/Stubs/TrueCondition.php';
require __DIR__ . '/Stubs/FalseCondition.php';

use BeatSwitch\Lock\Drivers\ArrayDriver;
use BeatSwitch\Lock\Callers\NullCaller;
use PhpSpec\ObjectBehavior;
use spec\BeatSwitch\Lock\Stubs\CallerStub;
use spec\BeatSwitch\Lock\Stubs\FalseCondition;
use spec\BeatSwitch\Lock\Stubs\TrueCondition;

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

    function it_can_handle_a_single_action()
    {
        $this->allow('edit');

        $this->can('edit')->shouldReturn(true);
        $this->cannot('edit')->shouldReturn(false);
        $this->can('edit', 'users')->shouldReturn(true);
        $this->can('edit', 'users', 1)->shouldReturn(true);
    }

    function it_can_handle_multiple_actions()
    {
        $this->allow(['create', 'edit']);

        $this->can('create')->shouldReturn(true);
        $this->can('edit')->shouldReturn(true);
    }

    function it_can_handle_a_resource_type()
    {
        $this->allow('edit', 'users');

        $this->can('edit')->shouldReturn(false);
        $this->cannot('edit')->shouldReturn(true);
        $this->can('edit', 'users')->shouldReturn(true);
        $this->can('edit', 'users', 1)->shouldReturn(true);
    }

    function it_can_handle_multiple_actions_on_a_resource_type()
    {
        $this->allow(['create', 'edit'], 'users');

        $this->can('create')->shouldReturn(false);
        $this->can('edit')->shouldReturn(false);
        $this->can('create', 'users')->shouldReturn(true);
        $this->can('edit', 'users')->shouldReturn(true);
        $this->can('update', 'users')->shouldReturn(false);
    }

    function it_can_handle_a_specific_resource()
    {
        $this->allow('edit', 'users', 5);

        $this->can('edit')->shouldReturn(false);
        $this->cannot('edit')->shouldReturn(true);
        $this->can('edit', 'users')->shouldReturn(false);
        $this->can('edit', 'users', 1)->shouldReturn(false);
        $this->can('edit', 'users', 5)->shouldReturn(true);
    }

    function it_can_handle_multiple_actions_on_a_specific_resource()
    {
        $this->allow(['create', 'edit'], 'users', 1);

        $this->can('create')->shouldReturn(false);
        $this->can('edit')->shouldReturn(false);
        $this->can('create', 'users')->shouldReturn(false);
        $this->can('create', 'users', 1)->shouldReturn(true);
        $this->can('edit', 'users',  1)->shouldReturn(true);
        $this->can('update', 'users',  1)->shouldReturn(false);
    }

    function it_can_handle_a_wildcard()
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

    function it_can_check_multiple_actions_at_once()
    {
        $this->allow(['create', 'edit']);

        $this->can(['create', 'edit'])->shouldReturn(true);
        $this->can(['create', 'delete'])->shouldReturn(false);
    }

    function it_can_toggle_permissions()
    {
        $this->toggle('edit', 'users');
        $this->can('edit', 'users')->shouldReturn(true);

        $this->toggle('edit', 'users');
        $this->can('edit', 'users')->shouldReturn(false);
    }

    function it_can_toggle_multiple_actions_at_once()
    {
        $this->toggle(['create', 'edit'], 'users');
        $this->can(['create', 'edit'], 'users')->shouldReturn(true);

        $this->toggle('edit', 'users');
        $this->can(['create', 'edit'], 'users')->shouldReturn(false);
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
        $this->setRoles(['editor', 'admin'], 'user');

        $this->allowRole('user', 'create', 'posts');
        $this->allowRoles(['editor', 'admin'], 'publish', 'posts');
        $this->allowRole('admin', 'delete', 'posts');

        // Our CallerStub has the editor role.
        $this->can(['create', 'publish'], 'posts')->shouldReturn(true);
        $this->can('delete', 'posts')->shouldReturn(false);
    }

    function it_can_work_with_permission_conditions()
    {
        $this->allow('create', 'posts', null, [new TrueCondition()]);
        $this->allow('create', 'pages', null, [new FalseCondition()]);

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
