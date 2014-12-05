<?php
namespace stubs\BeatSwitch\Lock;

use BeatSwitch\Lock\Lock;
use BeatSwitch\Lock\Permissions\Condition;
use BeatSwitch\Lock\Permissions\Permission;
use BeatSwitch\Lock\Resources\Resource;

class TrueConditionStub implements Condition
{
    /**
     * Assert if the condition is correct
     *
     * @param \BeatSwitch\Lock\Lock $lock
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     * @param string $action
     * @param \BeatSwitch\Lock\Resources\Resource|null $resource
     * @return bool
     */
    public function assert(Lock $lock, Permission $permission, $action, Resource $resource = null)
    {
        return true;
    }
}
