<?php
namespace spec\BeatSwitch\Lock\Stubs;

use BeatSwitch\Lock\Contracts\Condition;
use BeatSwitch\Lock\Contracts\Resource;

class FalseCondition implements Condition
{
    /**
     * Assert if the condition is correct
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Contracts\Resource $resource
     * @return bool
     */
    public function assert($action, Resource $resource)
    {
        return false;
    }
}
