<?php
namespace spec\BeatSwitch\Lock\Drivers;

require __DIR__ . '/../Stubs/CallerStub.php';

use BeatSwitch\Lock\Permissions\Privilege;
use BeatSwitch\Lock\Resource;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use spec\BeatSwitch\Lock\Stubs\CallerStub;

class ArrayDriverSpec extends ObjectBehavior
{
    private $caller1;
    private $caller2;
    private $caller3;

    function let()
    {
        $this->caller1 = new CallerStub('users', 1);
        $this->caller2 = new CallerStub('users', 2);
        $this->caller3 = new CallerStub('users', 3);

        $this->storePermission($this->caller1, new Privilege('read'));
        $this->storePermission($this->caller1, new Privilege('edit', new Resource('users', 1)));
        $this->storePermission($this->caller1, new Privilege('manage', new Resource('tasks')));
        $this->storePermission($this->caller2, new Privilege('edit', new Resource('events')));
        $this->storePermission($this->caller2, new Privilege('delete', new Resource('events')));
        $this->storePermission($this->caller3, new Privilege('create', new Resource('users')));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('BeatSwitch\Lock\Drivers\ArrayDriver');
        $this->shouldImplement('BeatSwitch\Lock\Contracts\Driver');
    }

    function it_returns_permissions()
    {
        $this->getPermissions($this->caller1)->shouldHaveCount(3);
    }

    function it_stores_a_permission()
    {
        $this->storePermission($this->caller2, new Privilege('create', new Resource('events')));
        $this->getPermissions($this->caller2)->shouldHaveCount(3);
    }

    function it_removes_a_permission()
    {
        $this->removePermission($this->caller1, new Privilege('manage', new Resource('tasks')));
        $this->getPermissions($this->caller1)->shouldHaveCount(2);
    }

    function it_can_confirm_it_has_a_permission()
    {
        $this->hasPermission($this->caller1, new Privilege('manage', new Resource('tasks')))->shouldReturn(true);
        $this->hasPermission($this->caller1, new Privilege('edit', new Resource('events')))->shouldReturn(false);
    }
}
