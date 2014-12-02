<?php
namespace spec\BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Resource;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PrivilegeSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('edit', new Resource('events', 1));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('BeatSwitch\Lock\Permissions\Privilege');
        $this->shouldImplement('BeatSwitch\Lock\Contracts\Permission');
    }

    function it_can_validate_itself_against_parameters()
    {
        $this->isAllowed('edit', new Resource('events', 1))->shouldReturn(true);
        $this->isAllowed('edit', new Resource('events', 1))->shouldReturn(true);
        $this->isAllowed('edit', new Resource('events', 2))->shouldReturn(false);
        $this->isAllowed('delete', new Resource('comments', 1))->shouldReturn(false);
    }

    function it_can_match_an_equal_permission()
    {
        $this->matchesPermission($this)->shouldReturn(true);
    }
}
