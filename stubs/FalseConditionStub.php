<?php
namespace stubs\BeatSwitch\Lock;

use BeatSwitch\Lock\Permissions\Condition;
use BeatSwitch\Lock\Resources\Resource;

class FalseConditionStub implements Condition
{
    /**
     * Assert if the condition is correct
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Resources\Resource|null $resource
     * @return bool
     */
    public function assert($action, Resource $resource = null)
    {
        return false;
    }
}
