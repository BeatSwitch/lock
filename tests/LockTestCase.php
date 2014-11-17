<?php
namespace BeatSwitch\Lock\Tests;

use BeatSwitch\Lock\Contracts\Caller;
use BeatSwitch\Lock\Contracts\Driver;
use BeatSwitch\Lock\Lock;
use BeatSwitch\Lock\Tests\Stubs\Event;
use BeatSwitch\Lock\Tests\Stubs\User;

abstract class LockTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * The main Lock instance
     *
     * @var \BeatSwitch\Lock\Lock
     */
    protected $lock;

    /**
     * The caller used to instantiate the main Lock instance
     *
     * @var \BeatSwitch\Lock\Tests\Stubs\User
     */
    protected $caller;

    /**
     * The driver used to instantiate the main Lock instance
     *
     * @var \BeatSwitch\Lock\Contracts\Driver
     */
    protected $driver;

    function setUp()
    {
        parent::setUp();

        $this->caller = new User(1);
        $this->caller->initLockInstance($this->driver);

        $this->lock = $this->makeLock($this->caller, $this->driver);
    }

    /**
     * The configuration with tests which all driver tests should use
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @param \BeatSwitch\Lock\Contracts\Driver $driver
     * @return \BeatSwitch\Lock\Lock
     */
    final protected function makeLock(Caller $caller = null, Driver $driver = null)
    {
        $lock = new Lock($caller, $driver);

        // Allow to create everything.
        $lock->allow('create');

        // Allow to update everything.
        $lock->allow('update');

        // Deny to update everything.
        $lock->deny('update');

        // Allow to delete events.
        $lock->allow('delete', 'events');

        // Set and remove permission on events.
        $lock->allow('export', 'events');
        $lock->deny('export', 'events');

        // Allow to do everything with posts.
        $lock->allow('all', 'posts');

        // Allow to edit this specific event with an ID of 1.
        $lock->allow('update', new Event(1));

        return $lock;
    }

    /** @test */
    final function it_succeeds_with_a_valid_action()
    {
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
        // Note that we're using the same event stub from the makeLock method.
        $this->assertTrue($this->lock->can('update', new Event(1)));
    }

    /** @test */
    final function it_fails_with_an_invalid_action_on_a_resource_object()
    {
        // Note that we're using the same event stub from the makeLock method.
        $this->assertFalse($this->lock->can('edit', new Event(1)));
    }

    /** @test */
    final function it_fails_with_a_denied_action_on_a_resource_type()
    {
        $this->assertFalse($this->lock->can('export', 'events'));
    }

    /** @test */
    final function it_always_succeeds_with_the_all_action()
    {
        $this->assertTrue($this->lock->can('create', 'posts'));
        $this->assertTrue($this->lock->can('update', 'posts'));
        $this->assertTrue($this->lock->can('delete', 'posts'));
    }

    /** @test */
    function it_fails_with_a_denied_action_for_a_resource_type()
    {
        $this->assertFalse($this->lock->can('update', 'events'));
    }

    /** @test */
    final function it_succeeds_when_overriding_a_denied_action_on_a_resource()
    {
        // Note that we're using the same event stub from the makeLock method.
        $this->assertTrue($this->lock->can('update', new Event(1)));
    }

    /** @test */
    final function it_fails_with_an_incorrect_resource_object()
    {
        // Note that we're using the same event stub from the makeLock method.
        $event = new Event(1);
        $event->id = 2;

        $this->assertFalse($this->lock->can('update', $event));
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
    final function the_caller_can_call_the_caller_trait_methods()
    {
        $this->assertTrue($this->caller->can('create'));
        $this->assertTrue($this->caller->cannot('update'));

        $this->caller->allow('update');
        $this->assertTrue($this->caller->can('update'));

        $this->caller->deny('update');
        $this->assertFalse($this->caller->can('update'));

        $this->caller->toggle('update');
        $this->assertTrue($this->caller->can('update'));
    }
}
