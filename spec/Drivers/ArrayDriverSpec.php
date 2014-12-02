<?php
namespace spec\BeatSwitch\Lock\Drivers;

// Import stubs
require_once __DIR__ . '/../../stubs/CallerStub.php';

use BeatSwitch\Lock\Permissions\Privilege;
use BeatSwitch\Lock\Resource;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use stubs\BeatSwitch\Lock\CallerStub;

class ArrayDriverSpec extends ObjectBehavior
{
    /**
     * @var \BeatSwitch\Lock\Callers\Caller
     */
    protected $caller;

    function let()
    {
        $this->caller = new CallerStub('users', 1);

        $this->storePermission($this->caller, new Privilege('read'));
        $this->storePermission($this->caller, new Privilege('edit', new Resource('users', 1)));
        $this->storePermission($this->caller, new Privilege('manage', new Resource('tasks')));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('BeatSwitch\Lock\Drivers\ArrayDriver');
        $this->shouldImplement('BeatSwitch\Lock\Drivers\Driver');
    }

    function it_returns_permissions()
    {
        $this->getPermissions($this->caller)->shouldHaveCount(3);
    }

    function it_stores_a_permission()
    {
        $this->storePermission($this->caller, new Privilege('create', new Resource('events')));
        $this->getPermissions($this->caller)->shouldHaveCount(4);
    }

    function it_removes_a_permission()
    {
        $this->removePermission($this->caller, new Privilege('manage', new Resource('tasks')));
        $this->getPermissions($this->caller)->shouldHaveCount(2);
    }

    function it_can_confirm_it_has_a_permission()
    {
        $this->hasPermission($this->caller, new Privilege('manage', new Resource('tasks')))->shouldReturn(true);
        $this->hasPermission($this->caller, new Privilege('edit', new Resource('events')))->shouldReturn(false);
    }
}
