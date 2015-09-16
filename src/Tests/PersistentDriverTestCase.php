<?php
namespace BeatSwitch\Lock\Tests;

use BeatSwitch\Lock\Callers\SimpleCaller;
use BeatSwitch\Lock\Manager;
use BeatSwitch\Lock\Resources\SimpleResource;

/**
 * The PersistentDriverTestCase can be used to test persistent drivers
 */
abstract class PersistentDriverTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * The main Lock instance
     *
     * @var \BeatSwitch\Lock\Manager
     */
    protected $manager;

    /**
     * The caller used to instantiate the main Lock instance
     *
     * @var \BeatSwitch\Lock\Callers\Caller
     */
    protected $caller;

    /**
     * The driver used to instantiate the main Lock instance
     *
     * @var \BeatSwitch\Lock\Drivers\Driver
     */
    protected $driver;

    function setUp()
    {
        parent::setUp();

        // Init the lock manager.
        $this->manager = new Manager($this->driver);

        // Init the caller.
        $this->caller = new SimpleCaller('users', 1, ['editor']);
        $this->caller->setLock($this->getCallerLock());
    }

    /**
     * @return \BeatSwitch\Lock\Callers\CallerLock
     */
    protected function getCallerLock()
    {
        return $this->manager->caller($this->caller);
    }

    /**
     * @param \BeatSwitch\Lock\Roles\Role|string $role
     * @return \BeatSwitch\Lock\Roles\RoleLock
     */
    protected function getRoleLock($role)
    {
        return $this->manager->role($role);
    }

    /** @test */
    final function it_succeeds_with_a_valid_action()
    {
        $lock = $this->getCallerLock();

        $lock->allow('create');

        $this->assertTrue($lock->can('create'));
    }

    /** @test */
    final function it_fails_with_an_invalid_action()
    {
        $this->assertFalse($this->getCallerLock()->can('edit'));
    }

    /** @test */
    final function it_fails_with_a_denied_action()
    {
        $lock = $this->getCallerLock();

        $lock->allow('update');
        $lock->deny('update');

        $this->assertFalse($lock->can('update'));
    }

    /** @test */
    final function it_fails_with_a_cleared_action()
    {
        $lock = $this->getCallerLock();

        $lock->allow('update');
        $lock->clear('update');

        $this->assertFalse($lock->can('update'));
    }

    /** @test */
    final function it_succeeds_with_an_inverse_check()
    {
        $this->assertTrue($this->getCallerLock()->cannot('update'));
    }

    /** @test */
    final function it_succeeds_with_a_valid_resource_type()
    {
        $lock = $this->getCallerLock();

        $lock->allow('delete', 'events');

        $this->assertTrue($lock->can('delete', 'events'));
    }

    /** @test */
    final function it_fails_with_an_invalid_resource_type()
    {
        $this->assertFalse($this->getCallerLock()->can('delete', 'pages'));
    }

    /** @test */
    final function it_succeeds_with_a_valid_action_on_a_resource_object()
    {
        $lock = $this->getCallerLock();
        $event = new SimpleResource('events', 1);

        $lock->allow('read', $event);

        $this->assertTrue($lock->can('read', $event));
    }

    /** @test */
    final function it_fails_with_an_invalid_action_on_a_resource_object()
    {
        $this->assertFalse($this->getCallerLock()->can('edit', new SimpleResource('events', 1)));
    }

    /** @test */
    final function it_fails_with_a_denied_action_on_a_resource_type()
    {
        $lock = $this->getCallerLock();

        $lock->allow('export', 'events');
        $lock->deny('export', 'events');

        $this->assertFalse($lock->can('export', 'events'));
    }

    /** @test */
    final function it_fails_with_a_cleared_action_on_a_resource_type()
    {
        $lock = $this->getCallerLock();

        $lock->allow('export', 'events');
        $lock->clear('export', 'events');

        $this->assertFalse($lock->can('export', 'events'));
    }

    /** @test */
    final function it_always_succeeds_with_the_all_action()
    {
        $lock = $this->getCallerLock();

        $lock->allow('all', 'posts');

        $this->assertTrue($lock->can('create', 'posts'));
        $this->assertTrue($lock->can('update', 'posts'));
        $this->assertTrue($lock->can('delete', 'posts'));

        // But we can't just call every action for every resource type.
        $this->assertFalse($lock->can('create', 'events'));
    }

    /** @test */
    final function it_always_fails_when_permissions_have_been_cleared_with_the_all_action()
    {
        $lock = $this->getCallerLock();

        $lock->allow('all', 'posts');
        $lock->clear('all', 'posts');

        $this->assertFalse($lock->can('create', 'posts'));
        $this->assertFalse($lock->can('update', 'posts'));
        $this->assertFalse($lock->can('delete', 'posts'));
    }

    /** @test */
    function it_fails_with_a_denied_action_for_a_resource_type()
    {
        $lock = $this->getCallerLock();

        $lock->allow('update', new SimpleResource('events', 1));

        // We can't update every event, just the one with an ID of 1.
        $this->assertFalse($lock->can('update', 'events'));
    }

    /** @test */
    final function it_succeeds_when_overriding_a_denied_action_on_a_resource()
    {
        $lock = $this->getCallerLock();
        $stub = new SimpleResource('events', 1);

        $lock->deny('update');
        $lock->allow('update', $stub);

        $this->assertTrue($lock->can('update', $stub));
    }

    /** @test */
    final function it_fails_with_an_incorrect_resource_object()
    {
        $lock = $this->getCallerLock();

        $lock->allow('update', new SimpleResource('events', 1));

        $this->assertFalse($lock->can('update', new SimpleResource('events', 2)));
    }

    /** @test */
    final function it_can_check_multiple_permissions_at_once()
    {
        $lock = $this->getCallerLock();

        $lock->allow(['create', 'delete'], 'comments');

        $this->assertTrue($lock->can(['create', 'delete'], 'comments'));
        $this->assertTrue($lock->cannot(['create', 'edit'], 'comments'));
    }

    /** @test */
    final function it_can_clear_multiple_permissions_at_once()
    {
        $lock = $this->getCallerLock();

        $lock->allow(['create', 'update', 'delete'], 'comments');
        $lock->clear(['create', 'delete'], 'comments');

        $this->assertTrue($lock->can('update', 'comments'));
        $this->assertTrue($lock->cannot(['create', 'delete'], 'comments'));
    }

    /** @test */
    final function it_only_clears_the_requested_permissions()
    {
        $lock = $this->getCallerLock();

        $lock->allow(['create', 'update', 'delete']);
        $lock->clear(['update', 'delete']);

        $this->assertTrue($lock->cannot(['update', 'delete']));
        $this->assertTrue($lock->can('create'));
    }

    /** @test */
    final function it_only_clears_permissions_on_the_given_resource()
    {
        $lock = $this->getCallerLock();

        $lock->allow(['create', 'update', 'delete'], 'comments');
        $lock->allow(['create', 'update', 'delete'], 'posts');
        $lock->clear(['create', 'update', 'delete'], 'comments');

        $this->assertFalse($lock->can(['create', 'update', 'delete'], 'comments'));
        $this->assertTrue($lock->can(['create', 'update', 'delete'], 'posts'));
    }

    /** @test */
    final function it_can_clear_every_single_permission_for_a_lock_instance()
    {
        $callerLock = $this->getCallerLock();
        $roleLock = $this->getRoleLock('admin');

        $callerLock->allow('manage-users');
        $callerLock->allow(['create', 'update', 'delete'], 'posts');
        $roleLock->allow('manage-users');
        $callerLock->clear();

        $this->assertFalse($callerLock->can('manage-users'));
        $this->assertFalse($callerLock->can('update', 'posts'));
        $this->assertFalse($callerLock->can('update', 'posts'));

        // Other callers stay unaffected.
        $this->assertTrue($roleLock->can('manage-users'));
    }

    /** @todo */
    final function it_can_clear_all_permissions_for_a_given_resource()
    {
        $lock = $this->getCallerLock();

        $lock->allow('manage-users');
        $lock->allow(['create', 'update', 'delete'], 'posts');
        $lock->allow(['create', 'update', 'delete'], 'comments');
        $lock->clear(null, 'posts');

        $this->assertTrue($lock->can('manage-users'));
        $this->assertTrue($lock->can('update', 'comments'));
        $this->assertFalse($lock->can('update', 'posts'));
        $this->assertFalse($lock->can('create', 'posts'));
    }

    /** @test */
    final function it_can_toggle_permissions()
    {
        $lock = $this->getCallerLock();

        $lock->toggle('edit', 'events');
        $this->assertTrue($lock->can('edit', 'events'));

        $lock->toggle('edit', 'events');
        $this->assertFalse($lock->can('edit', 'events'));
    }

    /** @test */
    final function it_can_toggle_multiple_permissions_at_once()
    {
        $lock = $this->getCallerLock();

        $lock->allow(['create', 'delete'], 'comments');

        $lock->toggle(['create', 'delete'], 'comments');
        $this->assertFalse($lock->can(['create', 'delete'], 'comments'));

        $lock->toggle(['create', 'delete'], 'comments');
        $this->assertTrue($lock->can(['create', 'delete'], 'comments'));
    }

    /** @test */
    final function the_caller_can_call_the_caller_trait_methods()
    {
        $this->caller->allow('create');

        $this->assertTrue($this->caller->can('create'));

        $this->caller->deny('create');
        $this->assertFalse($this->caller->can('create'));

        $this->caller->toggle('update');
        $this->assertTrue($this->caller->can('update'));

        $this->caller->clear('update');
        $this->assertFalse($this->caller->can('update'));
    }

    /** @test */
    final function it_can_check_actions_from_aliases()
    {
        $this->manager->alias('manage', ['create', 'read', 'update', 'delete']);

        $lock = $this->getCallerLock();
        $lock->allow('manage', 'accounts');

        $this->assertFalse($lock->can('manage'));
        $this->assertTrue($lock->can('manage', 'accounts'));
        $this->assertTrue($lock->can('manage', 'accounts', 1));
        $this->assertFalse($lock->can('manage', 'events'));
        $this->assertTrue($lock->can('read', 'accounts'));
        $this->assertTrue($lock->can(['read', 'update'], 'accounts'));

        // If one of the aliased actions is explicitly denied, it cannot pass anymore.
        $lock->deny('create');

        $this->assertTrue($lock->can('manage', 'accounts'));
        $this->assertFalse($lock->can('create', 'accounts'));
        $this->assertTrue($lock->can(['read', 'update', 'delete'], 'accounts'));
    }

    /** @test */
    final function it_can_clear_permissions_using_action_aliases()
    {
        $this->manager->alias('manage', ['read', 'write']);

        $lock = $this->getCallerLock();
        $lock->allow('manage', 'accounts');

        $this->assertTrue($lock->can('manage', 'accounts'));
        $this->assertTrue($lock->can('read', 'accounts'));
        $this->assertTrue($lock->can('write', 'accounts'));

        $lock->clear('manage', 'accounts');
        $this->assertTrue($lock->cannot('manage', 'accounts'));
        $this->assertTrue($lock->cannot('read', 'accounts'));
        $this->assertTrue($lock->cannot('write', 'accounts'));
    }

    /** @test */
    final function it_can_work_with_roles()
    {
        $this->manager->setRole('user');
        $this->manager->setRole(['editor', 'admin'], 'user');

        $this->getRoleLock('user')->allow('create', 'pages');
        $this->getRoleLock('editor')->allow('publish', 'pages');
        $this->getRoleLock('admin')->allow(['delete', 'publish'], 'pages');

        $lock = $this->getCallerLock();

        $this->assertTrue($lock->can(['create', 'publish'], 'pages'));
        $this->assertFalse($lock->can('delete', 'pages'));

        // If we deny the user from publishing anything afterwards, our role permissions are invalid.
        $lock->deny('publish');
        $this->assertFalse($lock->can(['create', 'publish'], 'pages'));
    }

    /** @test */
    final function caller_permissions_override_role_permissions()
    {
        $lock = $this->getCallerLock();
        $lock->allow('create', 'posts');

        $this->getRoleLock('user')->deny('create', 'posts');

        $this->assertTrue($lock->can('create', 'posts'));
    }

    /** @test */
    final function it_can_make_a_caller_lock_aware()
    {
        $this->getCallerLock()->allow('create', 'users');

        $caller = $this->manager->makeCallerLockAware($this->caller);

        $this->assertTrue($caller->can('create', 'users'));
    }

    /** @test */
    final function it_can_make_a_role_lock_aware()
    {
        $this->getRoleLock('admin')->allow('create', 'users');

        $role = $this->manager->makeRoleLockAware('admin');

        $this->assertTrue($role->can('create', 'users'));
    }

    /** @test */
    final function it_can_return_allowed_resource_ids()
    {
        $this->getCallerLock()->allow('update', 'users', 1);
        $this->getCallerLock()->allow('update', 'users', 2);
        $this->getCallerLock()->allow('update', 'events', 4);
        $this->getCallerLock()->allow(['update', 'delete'], 'users', 3);
        $this->getCallerLock()->allow(['update', 'delete'], 'users', 5);
        $this->getCallerLock()->allow('delete', 'users', 2);
        $this->getCallerLock()->deny('update', 'users', 2);

        $this->assertEquals([1, 3, 5], $this->getCallerLock()->allowed('update', 'users'));
        $this->assertEquals([3, 5, 2], $this->getCallerLock()->allowed('delete', 'users'));
        $this->assertEquals([3, 5], $this->getCallerLock()->allowed(['update', 'delete'], 'users'));
    }

    /** @test */
    final function it_can_return_denied_resource_ids()
    {
        $this->getCallerLock()->allow('update', 'users', 1);
        $this->getCallerLock()->allow('update', 'users', 2);
        $this->getCallerLock()->allow('update', 'events', 4);
        $this->getCallerLock()->allow(['update', 'delete'], 'users', 3);
        $this->getCallerLock()->allow(['update', 'delete'], 'users', 5);
        $this->getCallerLock()->deny('delete', 'users', 1);
        $this->getCallerLock()->allow('delete', 'users', 2);
        $this->getCallerLock()->deny('update', 'users', 2);

        $this->assertEquals([2], $this->getCallerLock()->denied('update', 'users'));
        $this->assertEquals([1], $this->getCallerLock()->denied('delete', 'users'));
        $this->assertEquals([1, 2], $this->getCallerLock()->denied(['update', 'delete'], 'users'));
    }
}
