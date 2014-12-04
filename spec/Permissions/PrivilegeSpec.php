<?php
namespace spec\BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Resources\SimpleResource;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PrivilegeSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('edit', new SimpleResource('events', 1));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('BeatSwitch\Lock\Permissions\Privilege');
        $this->shouldImplement('BeatSwitch\Lock\Permissions\Permission');
    }

    function it_can_validate_itself_against_parameters()
    {
        $this->isAllowed('edit', new SimpleResource('events', 1))->shouldReturn(true);
        $this->isAllowed('edit', new SimpleResource('events', 1))->shouldReturn(true);
        $this->isAllowed('edit', new SimpleResource('events', 2))->shouldReturn(false);
        $this->isAllowed('delete', new SimpleResource('comments', 1))->shouldReturn(false);
    }

    function it_can_match_an_equal_permission()
    {
        $this->matchesPermission($this)->shouldReturn(true);
    }
}
