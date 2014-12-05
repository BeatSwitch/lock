<?php
namespace spec\BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Callers\CallerLock;
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

    function it_can_validate_itself_against_parameters(CallerLock $lock)
    {
        $this->isAllowed($lock, 'edit', new SimpleResource('events', 1))->shouldReturn(true);
        $this->isAllowed($lock, 'edit', new SimpleResource('events', 1))->shouldReturn(true);
        $this->isAllowed($lock, 'edit', new SimpleResource('events', 2))->shouldReturn(false);
        $this->isAllowed($lock, 'delete', new SimpleResource('comments', 1))->shouldReturn(false);
    }

    function it_can_match_an_equal_permission()
    {
        $this->matchesPermission($this)->shouldReturn(true);
    }

    function it_fails_with_a_false_condition(CallerLock $lock)
    {
        $this->beConstructedWith('edit', new SimpleResource('events', 1), function () {
            return false;
        });

        $this->isAllowed($lock, 'edit', new SimpleResource('events', 1))->shouldReturn(false);
    }
}
