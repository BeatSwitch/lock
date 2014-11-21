<?php
namespace BeatSwitch\Lock\Contracts;

interface Condition
{
    /**
     * Assert if the condition is correct
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Contracts\Resource $resource
     * @return bool
     */
    public function assert($action, Resource $resource);
}
