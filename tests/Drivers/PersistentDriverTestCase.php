<?php
namespace tests\BeatSwitch\Lock\Drivers;

use BeatSwitch\Lock\Manager;
use BeatSwitch\Lock\Resource;
use stubs\BeatSwitch\Lock\CallerStub;

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
     * The main Lock instance
     *
     * @var \BeatSwitch\Lock\Lock
     */
    protected $lock;

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
        $caller = new CallerStub('users', 1, ['editor']);

        // Create the lock instance.
        $this->lock = $this->manager->caller($caller);

        // Set the Lock instance on the caller to init the trait functionality.
        $caller->setLock($this->lock);
        $this->caller = $caller;
    }

    /** @test */
    final function it_succeeds_with_a_valid_action()
    {
        $this->lock->allow('create');

        $this->assertTrue($this->lock->can('create'));
    }

    /** @test */
    final function it_fails_with_an_invalid_action()
    {
        $this->assertFalse($this->lock->can('edit'));
    }

    /** @test */
    final function it_fails_with_a_denied_action()
    {
        $this->lock->allow('update');
        $this->lock->deny('update');

        $this->assertFalse($this->lock->can('update'));
    }

    /** @test */
    final function it_succeeds_with_an_inverse_check()
    {
        $this->assertTrue($this->lock->cannot('update'));
    }

    /** @test */
    final function it_succeeds_with_a_valid_resource_type()
    {
        $this->lock->allow('delete', 'events');

        $this->assertTrue($this->lock->can('delete', 'events'));
    }

    /** @test */
    final function it_fails_with_an_invalid_resource_type()
    {
        $this->assertFalse($this->lock->can('delete', 'pages'));
    }

    /** @test */
    final function it_succeeds_with_a_valid_action_on_a_resource_object()
    {
        $event = new Resource('events', 1);
        $this->lock->allow('read', $event);
        $this->assertTrue($this->lock->can('read', $event));
    }

    /** @test */
    final function it_fails_with_an_invalid_action_on_a_resource_object()
    {
        $this->assertFalse($this->lock->can('edit', new Resource('events', 1)));
    }

    /** @test */
    final function it_fails_with_a_denied_action_on_a_resource_type()
    {
        $this->lock->allow('export', 'events');
        $this->lock->deny('export', 'events');

        $this->assertFalse($this->lock->can('export', 'events'));
    }

    /** @test */
    final function it_always_succeeds_with_the_all_action()
    {
        $this->lock->allow('all', 'posts');

        $this->assertTrue($this->lock->can('create', 'posts'));
        $this->assertTrue($this->lock->can('update', 'posts'));
        $this->assertTrue($this->lock->can('delete', 'posts'));

        // But we can't just call every action for every resource type.
        $this->assertFalse($this->lock->can('create', 'events'));
    }

    /** @test */
    function it_fails_with_a_denied_action_for_a_resource_type()
    {
        $this->lock->allow('update', new Resource('events', 1));

        // We can't update every event, just the one with an ID of 1.
        $this->assertFalse($this->lock->can('update', 'events'));
    }

    /** @test */
    final function it_succeeds_when_overriding_a_denied_action_on_a_resource()
    {
        $stub = new Resource('events', 1);

        $this->lock->deny('update');
        $this->lock->allow('update', $stub);

        $this->assertTrue($this->lock->can('update', $stub));
    }

    /** @test */
    final function it_fails_with_an_incorrect_resource_object()
    {
        $this->lock->allow('update', new Resource('events', 1));
        $this->assertFalse($this->lock->can('update', new Resource('events', 2)));
    }

    /** @test */
    final function it_can_check_multiple_permissions_at_once()
    {
        $this->lock->allow(['create', 'delete'], 'comments');

        $this->assertTrue($this->lock->can(['create', 'delete'], 'comments'));
        $this->assertTrue($this->lock->cannot(['create', 'edit'], 'comments'));
    }

    /** @test */
    final function it_can_toggle_permissions()
    {
        $this->lock->toggle('edit', 'events');
        $this->assertTrue($this->lock->can('edit', 'events'));

        $this->lock->toggle('edit', 'events');
        $this->assertFalse($this->lock->can('edit', 'events'));
    }

    /** @test */
    final function it_can_toggle_multiple_permissions_at_once()
    {
        $this->lock->allow(['create', 'delete'], 'comments');

        $this->lock->toggle(['create', 'delete'], 'comments');
        $this->assertFalse($this->lock->can(['create', 'delete'], 'comments'));

        $this->lock->toggle(['create', 'delete'], 'comments');
        $this->assertTrue($this->lock->can(['create', 'delete'], 'comments'));
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
    }

    /**
     * @test
     *
     * @todo Verify if the last assert of the "manage" action is the expected behavior
     * @todo Fix this test
     */
    final function it_can_check_actions_from_aliases()
    {
        $this->lock->alias('manage', ['create', 'read', 'update', 'delete']);
        $this->lock->allow('manage', 'accounts');

        $this->assertFalse($this->lock->can('manage'));
        $this->assertTrue($this->lock->can('manage', 'accounts'));
        $this->assertTrue($this->lock->can('manage', 'accounts', 1));
        $this->assertFalse($this->lock->can('manage', 'events'));
        $this->assertTrue($this->lock->can('read', 'accounts'));
        $this->assertTrue($this->lock->can(['read', 'update'], 'accounts'));

//        // If one of the aliased actions is explicitly denied, it cannot pass anymore.
//        $this->lock->deny('create');
//
//        $this->assertFalse($this->lock->can('manage', 'accounts'));
//        $this->assertFalse($this->lock->can('create', 'accounts'));
//        $this->assertTrue($this->lock->can(['read', 'update', 'delete'], 'accounts'));
    }

    /** @test */
    final function it_can_work_with_roles()
    {
        $this->lock->setRole('user');
        $this->lock->setRole(['editor', 'admin'], 'user');

        $this->lock->allowRole('user', 'create', 'pages');
        $this->lock->allowRole(['editor', 'admin'], 'publish', 'pages');
        $this->lock->allowRole('admin', 'delete', 'pages');

        $this->assertTrue($this->lock->can(['create', 'publish'], 'pages'));
        $this->assertFalse($this->lock->can('delete', 'pages'));

        // If we deny the user from publishing anything afterwards, our role permissions are invalid.
        $this->lock->deny('publish');
        $this->assertFalse($this->lock->can(['create', 'publish'], 'pages'));
    }
}
