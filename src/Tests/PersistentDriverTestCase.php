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
        $this->caller = new SimpleCaller('users', 1, ['editor', 'publisher']);
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
        $event = new SimpleResource('events', '1');

        $lock->allow('read', $event);

        $this->assertTrue($lock->can('read', $event));
    }

    /** @test */
    final function it_fails_with_an_invalid_action_on_a_resource_object()
    {
        $this->assertFalse($this->getCallerLock()->can('edit', new SimpleResource('events', '1')));
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
    function it_fails_with_a_denied_action_for_a_resource_type()
    {
        $lock = $this->getCallerLock();

        $lock->allow('update', new SimpleResource('events', '1'));

        // We can't update every event, just the one with an ID of 1.
        $this->assertFalse($lock->can('update', 'events'));
    }

    /** @test */
    final function it_succeeds_when_overriding_a_denied_action_on_a_resource()
    {
        $lock = $this->getCallerLock();
        $stub = new SimpleResource('events', '1');

        $lock->deny('update');
        $lock->allow('update', $stub);

        $this->assertTrue($lock->can('update', $stub));
    }

    /** @test */
    final function it_fails_with_an_incorrect_resource_object()
    {
        $lock = $this->getCallerLock();

        $lock->allow('update', new SimpleResource('events', '1'));

        $this->assertFalse($lock->can('update', new SimpleResource('events', '2')));
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
    }

    /** @test */
    final function it_can_check_actions_from_aliases()
    {
        $this->manager->alias('manage', ['create', 'read', 'update', 'delete']);

        $lock = $this->getCallerLock();
        $lock->allow('manage', 'accounts');

        $this->assertFalse($lock->can('manage'));
        $this->assertTrue($lock->can('manage', 'accounts'));
        $this->assertTrue($lock->can('manage', 'accounts', '1'));
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

        $this->getRoleLock('user')->deny('user', 'create', 'posts');

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

    /**
    * @test
    * @group failing
    */
    final function it_can_return_allowed_resource_ids()
    {
        $this->getCallerLock()->allow('update', 'users', '1');
        $this->getCallerLock()->allow('update', 'users', '2');
        $this->getCallerLock()->allow('update', 'events', '4');
        $this->getCallerLock()->allow(['update', 'delete'], 'users', '3');
        $this->getCallerLock()->allow(['update', 'delete'], 'users', '5');
        $this->getCallerLock()->allow('delete', 'users', '2');
        $this->getCallerLock()->deny('update', 'users', '2');

        $expected = ['1', '3', '5'];
        $result = $this->getCallerLock()->allowed('update', 'users');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $expected = ['3', '5', '2'];
        $result = $this->getCallerLock()->allowed('delete', 'users');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);
        
        $expected = ['3', '5'];
        $result = $this->getCallerLock()->allowed(['update', 'delete'], 'users');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

    }

    /** @test */
    final function it_can_return_denied_resource_ids()
    {
        $this->getCallerLock()->allow('update', 'users', '1');
        $this->getCallerLock()->allow('update', 'users', '2');
        $this->getCallerLock()->allow('update', 'events', '4');
        $this->getCallerLock()->allow(['update', 'delete'], 'users', '3');
        $this->getCallerLock()->allow(['update', 'delete'], 'users', '5');
        $this->getCallerLock()->deny('delete', 'users', '1');
        $this->getCallerLock()->allow('delete', 'users', '2');
        $this->getCallerLock()->deny('update', 'users', '2');

        $this->assertEquals(['2'], $this->getCallerLock()->denied('update', 'users'));
        $this->assertEquals(['1'], $this->getCallerLock()->denied('delete', 'users'));

        $expected = ['1', '2'];
        $result = $this->getCallerLock()->denied(['update', 'delete'], 'users');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->getCallerLock()->deny('update', 'users', '6');
        $expected = ['1', '2', '6'];
        $result = $this->getCallerLock()->denied(['update', 'delete'], 'users');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);
    }

    /** @test */
    final function it_can_clear_permissions(){
        // test one (permission set on caller)
        $this->getCallerLock()->allow('make', '1');
        // check if it was correctly set
        $this->assertTrue($this->getCallerLock()->can('make', '1'));
        // clear
        $this->getCallerLock()->clear('make', '1');
        $this->assertFalse($this->getCallerLock()->can('make', '1'));


        // test two (permission set on role herited by caller)
        $this->getRoleLock('editor')->allow('make', '2');
        // check if it was correctly set
        $this->assertTrue($this->getCallerLock()->can('make', '2'));
        //clear
        $this->getRoleLock('editor')->clear('make', '2');
        $this->assertFalse($this->getCallerLock()->can('make', '2'));


        // test three check if can clear deny 
        $this->getRoleLock('editor')->allow('make'); // allow 'make' on ALL
        $this->getCallerLock()->deny('make', '3');
        // check if it was correctly set
        $this->assertFalse($this->getCallerLock()->can('make', '3'));
        //clear
        $this->getCallerLock()->clear('make', '3');
        $this->assertTrue($this->getCallerLock()->can('make', '3'));
    }

    /** @test */
    final function it_can_return_allowed_resource_ids_with_inheritence(){
        $this->getRoleLock('editor')->allow('make', 'events', '1');
        $this->getRoleLock('editor')->allow('make', 'events', '2');
        $this->getCallerLock()->allow('make', 'events', '3');

        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $expected = ['1', '2', '3'];
        $result = $this->getCallerLock()->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3'));
        $expected = ['1', '2'];
        $result = $this->getRoleLock('editor')->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);



        $this->getRoleLock('editor')->allow('make', 'events', '4');
        $this->getCallerLock()->deny('make', 'events', '4');

        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $expected = ['1', '2', '3'];
        $result = $this->getCallerLock()->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '4'));
        $expected = ['1', '2', '4'];
        $result = $this->getRoleLock('editor')->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);



        $this->getRoleLock('editor')->deny('make', 'events', '5');
        $this->getCallerLock()->allow('make', 'events', '5');

        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $expected = ['1', '2', '3', '5'];
        $result = $this->getCallerLock()->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '4'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $expected = ['1', '2', '4'];
        $result = $this->getRoleLock('editor')->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('editor')->deny('make', 'events', '6');
        $this->getCallerLock()->deny('make', 'events', '6');

        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '6'));
        $expected = ['1', '2', '3', '5'];
        $result = $this->getCallerLock()->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '4'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '6'));
        $expected = ['1', '2', '4'];
        $result = $this->getRoleLock('editor')->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('editor')->allow('make', 'events', '7');
        $this->getCallerLock()->allow('make', 'events', '7');

        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '6'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '7'));
        $expected = ['1', '2', '3', '5', '7'];
        $result = $this->getCallerLock()->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '4'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '7'));
        $expected = ['1', '2', '4', '7'];
        $result = $this->getRoleLock('editor')->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('publisher')->allow('make', 'events', '8');


        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '6'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '7'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '8'));
        $expected = ['1', '2', '3', '5', '7', '8'];
        $result = $this->getCallerLock()->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '4'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '7'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '8'));
        $expected = ['1', '2', '4', '7'];
        $result = $this->getRoleLock('editor')->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '3'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '4'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '7'));
        $this->assertTrue($this->getRoleLock('publisher')->can('make', 'events', '8'));
        $expected = ['8'];
        $result = $this->getRoleLock('publisher')->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('editor')->allow('make', 'events', '9');
        $this->getRoleLock('publisher')->deny('make', 'events', '9');


        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '6'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '7'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '8'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '9'));
        $expected = ['1', '2', '3', '5', '7', '8', '9'];
        $result = $this->getCallerLock()->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '4'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '7'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '8'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '9'));
        $expected = ['1', '2', '4', '7', '9'];
        $result = $this->getRoleLock('editor')->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '3'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '4'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '7'));
        $this->assertTrue($this->getRoleLock('publisher')->can('make', 'events', '8'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '9'));
        $expected = ['8'];
        $result = $this->getRoleLock('publisher')->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('editor')->deny('make', 'events', '10');
        $this->getRoleLock('publisher')->allow('make', 'events', '10');


        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '6'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '7'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '8'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '9'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '10'));
        $expected = ['1', '2', '3', '5', '7', '8', '9', '10'];
        $result = $this->getCallerLock()->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '4'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '7'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '8'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '9'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '10'));
        $expected = ['1', '2', '4', '7', '9'];
        $result = $this->getRoleLock('editor')->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '3'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '4'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '7'));
        $this->assertTrue($this->getRoleLock('publisher')->can('make', 'events', '8'));
        $this->assertTrue($this->getRoleLock('publisher')->cannot('make', 'events', '9'));
        $this->assertTrue($this->getRoleLock('publisher')->can('make', 'events', '10'));
        $expected = ['8', '10'];
        $result = $this->getRoleLock('publisher')->allowed('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);
    }

    /**
    * @test
    */
    final function it_can_return_denied_resource_ids_with_inheritance() {
        $this->getRoleLock('editor')->allow('make', 'events', '1');
        $this->getRoleLock('editor')->deny('make', 'events', '2');
        $this->getCallerLock()->allow('make', 'events', '3');
        $this->getCallerLock()->deny('make', 'events', '4');

        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $expected = ['2', '4'];
        $result = $this->getCallerLock()->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '4')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $expected = ['2'];
        $result = $this->getRoleLock('editor')->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('editor')->deny('make', 'events', '5');
        $this->getCallerLock()->allow('make', 'events', '5');


        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $expected = ['2', '4'];
        $result = $this->getCallerLock()->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '4')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $expected = ['2', '5'];
        $result = $this->getRoleLock('editor')->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('editor')->allow('make', 'events', '6');
        $this->getCallerLock()->deny('make', 'events', '6');


        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '6'));
        $expected = ['2', '4', '6'];
        $result = $this->getCallerLock()->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '4')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '6'));
        $expected = ['2', '5'];
        $result = $this->getRoleLock('editor')->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('editor')->allow('make', 'events', '7');
        $this->getCallerLock()->allow('make', 'events', '7');


        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '6'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '7'));
        $expected = ['2', '4', '6'];
        $result = $this->getCallerLock()->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '4')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '7'));
        $expected = ['2', '5'];
        $result = $this->getRoleLock('editor')->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('editor')->deny('make', 'events', '8');
        $this->getCallerLock()->deny('make', 'events', '8');


        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '6'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '7'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '8'));
        $expected = ['2', '4', '6', '8'];
        $result = $this->getCallerLock()->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '4')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '7'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '8'));
        $expected = ['2', '5', '8'];
        $result = $this->getRoleLock('editor')->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('publisher')->deny('make', 'events', '9');


        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '6'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '7'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '8'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '9'));
        $expected = ['2', '4', '6', '8', '9'];
        $result = $this->getCallerLock()->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '4')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '7'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '8'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '9')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $expected = ['2', '5', '8'];
        $result = $this->getRoleLock('editor')->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '1')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '2')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '3')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '4')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '5')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '6')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '7')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '8')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '9')); 
        $expected = ['9'];
        $result = $this->getRoleLock('Publisher')->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('editor')->allow('make', 'events', '10');
        $this->getRoleLock('publisher')->deny('make', 'events', '10');


        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '6'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '7'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '8'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '9'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '10'));
        $expected = ['2', '4', '6', '8', '9'];
        $result = $this->getCallerLock()->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '4')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '7'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '8'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '9')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '10'));
        $expected = ['2', '5', '8'];
        $result = $this->getRoleLock('editor')->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '1')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '2')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '3')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '4')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '5')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '6')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '7')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '8')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '9'));
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '10')); 
        $expected = ['9', '10'];
        $result = $this->getRoleLock('Publisher')->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);


        $this->getRoleLock('editor')->deny('make', 'events', '11');
        $this->getRoleLock('publisher')->allow('make', 'events', '11');


        $this->assertTrue($this->getCallerLock()->can('make', 'events', '1'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '2'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '4'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '5'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '6'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '7'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '8'));
        $this->assertTrue($this->getCallerLock()->cannot('make', 'events', '9'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '10'));
        $this->assertTrue($this->getCallerLock()->can('make', 'events', '11'));
        $expected = ['2', '4', '6', '8', '9'];
        $result = $this->getCallerLock()->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '1'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '3')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '4')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '5'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '6'));
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '7'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '8'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '9')); // Editor as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('editor')->can('make', 'events', '10'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('make', 'events', '11'));
        $expected = ['2', '5', '8', '11'];
        $result = $this->getRoleLock('editor')->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '1')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '2')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '3')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '4')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '5')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '6')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '7')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '8')); // Publisher as no Permission on that (no Privilege neither Restriction) => won't be listed in denied
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '9'));
        $this->assertTrue($this->getRoleLock('Publisher')->cannot('make', 'events', '10')); 
        $this->assertTrue($this->getRoleLock('Publisher')->can('make', 'events', '11')); 
        $expected = ['9', '10'];
        $result = $this->getRoleLock('Publisher')->denied('make', 'events');
        sort($expected, SORT_STRING);
        sort($result, SORT_STRING);
        $this->assertEquals($expected,$result);

    }

    /**
    * @test
    */
    final function it_can_succeed_or_failed_on_explicit_call_ALL(){
        $this->getCallerLock()->allow('eat');
        $this->getCallerLock()->allow('sleep');
        $this->getRoleLock('editor')->allow('run');

        $this->assertTrue($this->getCallerLock()->can('eat'));
        $this->assertTrue($this->getCallerLock()->canExplicitly('eat'));
        $this->assertFalse($this->getRoleLock('editor')->can('eat'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('eat'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('eat'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('eat'));
        $this->assertTrue($this->getCallerLock()->can('run'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('run'));
        $this->assertTrue($this->getRoleLock('editor')->can('run'));
        $this->assertFalse($this->getRoleLock('editor')->cannot('run'));
        $this->assertTrue($this->getRoleLock('editor')->canExplicitly('run'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('run'));
        $this->assertTrue($this->getCallerLock()->can('sleep'));
        $this->assertTrue($this->getCallerLock()->canExplicitly('sleep'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('sleep'));
        

        $this->getCallerLock()->deny('standup');
        $this->getRoleLock('editor')->deny('jump');

        $this->assertFalse($this->getCallerLock()->can('standup'));
        $this->assertTrue($this->getCallerLock()->cannot('standup'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('standup'));
        $this->assertTrue($this->getCallerLock()->cannotExplicitly('standup'));
        $this->assertFalse($this->getRoleLock('editor')->can('standup'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('standup'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('standup'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('standup'));

        $this->assertFalse($this->getCallerLock()->can('jump'));
        $this->assertTrue($this->getCallerLock()->cannot('jump'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('jump'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('jump'));
        $this->assertFalse($this->getRoleLock('editor')->can('jump'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('jump'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('jump'));
        $this->assertTrue($this->getRoleLock('editor')->cannotExplicitly('jump'));

        $this->assertFalse($this->getCallerLock()->can('drive'));
        $this->assertTrue($this->getCallerLock()->cannot('drive'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('drive'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('drive'));


    }

    /**
    * @test
    */
    final function it_can_succeed_or_failed_on_explicit_call_ALL_RESOURCE_TYPE(){
        $this->getCallerLock()->allow('speak', 'teacher');
        $this->getCallerLock()->allow('talk', 'teacher');
        $this->getRoleLock('editor')->allow('move', 'teacher');

        $this->assertTrue($this->getCallerLock()->can('speak', 'teacher'));
        $this->assertTrue($this->getCallerLock()->canExplicitly('speak', 'teacher'));
        $this->assertFalse($this->getRoleLock('editor')->can('speak', 'teacher'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('speak', 'teacher'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('speak', 'teacher'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('speak', 'teacher'));
        $this->assertTrue($this->getCallerLock()->can('move', 'teacher'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('move', 'teacher'));
        $this->assertTrue($this->getRoleLock('editor')->can('move', 'teacher'));
        $this->assertFalse($this->getRoleLock('editor')->cannot('move', 'teacher'));
        $this->assertTrue($this->getRoleLock('editor')->canExplicitly('move', 'teacher'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('move', 'teacher'));
        $this->assertTrue($this->getCallerLock()->can('talk', 'teacher'));
        $this->assertTrue($this->getCallerLock()->canExplicitly('talk', 'teacher'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('talk', 'teacher'));


        $this->assertFalse($this->getCallerLock()->can('speak', 'student'));
        $this->assertTrue($this->getCallerLock()->cannot('speak', 'student'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('speak', 'student'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('speak', 'student'));
        $this->assertFalse($this->getRoleLock('editor')->can('speak', 'student'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('speak', 'student'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('speak', 'student'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('speak', 'student'));

        
        $this->getCallerLock()->deny('climb', 'teacher');
        $this->getRoleLock('editor')->deny('swim', 'teacher');

        $this->assertFalse($this->getCallerLock()->can('climb', 'teacher'));
        $this->assertTrue($this->getCallerLock()->cannot('climb', 'teacher'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('climb', 'teacher'));
        $this->assertTrue($this->getCallerLock()->cannotExplicitly('climb', 'teacher'));
        $this->assertFalse($this->getRoleLock('editor')->can('climb', 'teacher'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('climb', 'teacher'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('climb', 'teacher'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('climb', 'teacher'));

        $this->assertFalse($this->getCallerLock()->can('swim', 'teacher'));
        $this->assertTrue($this->getCallerLock()->cannot('swim', 'teacher'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('swim', 'teacher'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('swim', 'teacher'));
        $this->assertFalse($this->getRoleLock('editor')->can('swim', 'teacher'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('swim', 'teacher'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('swim', 'teacher'));
        $this->assertTrue($this->getRoleLock('editor')->cannotExplicitly('swim', 'teacher'));


        $this->assertFalse($this->getCallerLock()->can('climb', 'student'));
        $this->assertTrue($this->getCallerLock()->cannot('climb', 'student'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('climb', 'student'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('climb', 'student'));
        $this->assertFalse($this->getRoleLock('editor')->can('swim', 'student'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('swim', 'student'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('swim', 'student'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('swim', 'student'));
    }

    /**
    * @test
    */
    final function it_can_succeed_or_failed_on_explicit_call_ALL_RESOURCE_TYPE_AND_ID(){
        $this->getCallerLock()->allow('play', 'balloon', '3');
        $this->assertTrue($this->getCallerLock()->can('play', 'balloon', '3'));
        $this->assertFalse($this->getCallerLock()->cannot('play', 'balloon', '3'));
        $this->assertTrue($this->getCallerLock()->canExplicitly('play', 'balloon', '3'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('play', 'balloon', '3'));
        $this->assertFalse($this->getCallerLock()->can('play', 'balloon', '1'));
        $this->assertTrue($this->getCallerLock()->cannot('play', 'balloon', '1'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('play', 'balloon', '1'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('play', 'balloon', '1'));

        $this->getRoleLock('editor')->allow('play', 'balloon', '2');
        $this->assertTrue($this->getCallerLock()->can('play', 'balloon', '2'));
        $this->assertFalse($this->getCallerLock()->cannot('play', 'balloon', '2'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('play', 'balloon', '2'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('play', 'balloon', '2'));
        $this->assertTrue($this->getRoleLock('editor')->can('play', 'balloon', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannot('play', 'balloon', '2'));
        $this->assertTrue($this->getRoleLock('editor')->canExplicitly('play', 'balloon', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('play', 'balloon', '2'));

        $this->getCallerLock()->allow('play', 'pingpong');
        $this->assertTrue($this->getCallerLock()->can('play', 'pingpong', '2'));
        $this->assertFalse($this->getCallerLock()->cannot('play', 'pingpong', '2'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('play', 'pingpong', '2'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('play', 'pingpong', '2'));
        $this->assertFalse($this->getRoleLock('editor')->can('play', 'pingpong', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('play', 'pingpong', '2'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('play', 'pingpong', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('play', 'pingpong', '2'));

        $this->getRoleLock('editor')->allow('play', 'tennis');
        $this->assertTrue($this->getCallerLock()->can('play', 'tennis', '2'));
        $this->assertFalse($this->getCallerLock()->cannot('play', 'tennis', '2'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('play', 'tennis', '2'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('play', 'tennis', '2'));
        $this->assertTrue($this->getRoleLock('editor')->can('play', 'tennis', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannot('play', 'tennis', '2'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('play', 'tennis', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('play', 'tennis', '2'));

        $this->getCallerLock()->deny('play', 'basket', '3');
        $this->assertFalse($this->getCallerLock()->can('play', 'basket', '3'));
        $this->assertTrue($this->getCallerLock()->cannot('play', 'basket', '3'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('play', 'basket', '3'));
        $this->assertTrue($this->getCallerLock()->cannotExplicitly('play', 'basket', '3'));
        $this->assertFalse($this->getCallerLock()->can('play', 'basket', '1'));
        $this->assertTrue($this->getCallerLock()->cannot('play', 'basket', '1'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('play', 'basket', '1'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('play', 'basket', '1'));

        $this->getRoleLock('editor')->deny('play', 'basket', '2');
        $this->assertFalse($this->getCallerLock()->can('play', 'basket', '2'));
        $this->assertTrue($this->getCallerLock()->cannot('play', 'basket', '2'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('play', 'basket', '2'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('play', 'basket', '2'));
        $this->assertFalse($this->getRoleLock('editor')->can('play', 'basket', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('play', 'basket', '2'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('play', 'basket', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannotExplicitly('play', 'basket', '2'));

        $this->getCallerLock()->deny('play', 'kayak');
        $this->assertFalse($this->getCallerLock()->can('play', 'kayak', '2'));
        $this->assertTrue($this->getCallerLock()->cannot('play', 'kayak', '2'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('play', 'kayak', '2'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('play', 'kayak', '2'));
        $this->assertFalse($this->getRoleLock('editor')->can('play', 'kayak', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('play', 'kayak', '2'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('play', 'kayak', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('play', 'kayak', '2'));

        $this->getRoleLock('editor')->deny('play', 'gameboy');
        $this->assertFalse($this->getCallerLock()->can('play', 'gameboy', '2'));
        $this->assertTrue($this->getCallerLock()->cannot('play', 'gameboy', '2'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('play', 'gameboy', '2'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('play', 'gameboy', '2'));
        $this->assertFalse($this->getRoleLock('editor')->can('play', 'gameboy', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('play', 'gameboy', '2'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('play', 'gameboy', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('play', 'gameboy', '2'));

        $this->getCallerLock()->allow('drink');
        $this->assertTrue($this->getCallerLock()->can('drink', 'coca', '2'));
        $this->assertFalse($this->getCallerLock()->cannot('drink', 'coca', '2'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('drink', 'coca', '2'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('drink', 'coca', '2'));
        $this->assertFalse($this->getRoleLock('editor')->can('drink', 'coca', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('drink', 'coca', '2'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('drink', 'coca', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('drink', 'coca', '2'));

        $this->getRoleLock('editor')->allow('cook');
        $this->assertTrue($this->getCallerLock()->can('cook', 'cake', '2'));
        $this->assertFalse($this->getCallerLock()->cannot('cook', 'cake', '2'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('cook', 'cake', '2'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('cook', 'cake', '2'));
        $this->assertTrue($this->getRoleLock('editor')->can('cook', 'cake', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannot('cook', 'cake', '2'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('cook', 'cake', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('cook', 'cake', '2'));

        $this->getCallerLock()->deny('read');
        $this->assertFalse($this->getCallerLock()->can('read', 'book', '2'));
        $this->assertTrue($this->getCallerLock()->cannot('read', 'book', '2'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('read', 'book', '2'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('read', 'book', '2'));
        $this->assertFalse($this->getRoleLock('editor')->can('read', 'book', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('read', 'book', '2'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('read', 'book', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('read', 'book', '2'));

        $this->getRoleLock('editor')->deny('fake');
        $this->assertFalse($this->getCallerLock()->can('fake', 'love', '2'));
        $this->assertTrue($this->getCallerLock()->cannot('fake', 'love', '2'));
        $this->assertFalse($this->getCallerLock()->canExplicitly('fake', 'love', '2'));
        $this->assertFalse($this->getCallerLock()->cannotExplicitly('fake', 'love', '2'));
        $this->assertFalse($this->getRoleLock('editor')->can('fake', 'love', '2'));
        $this->assertTrue($this->getRoleLock('editor')->cannot('fake', 'love', '2'));
        $this->assertFalse($this->getRoleLock('editor')->canExplicitly('fake', 'love', '2'));
        $this->assertFalse($this->getRoleLock('editor')->cannotExplicitly('fake', 'love', '2'));

    }
}
