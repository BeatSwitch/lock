<?php
namespace BeatSwitch\Lock\Contracts;

/**
 * A contract to define a permission condition. Conditions need to give
 * back a true value if a permission is to succeed.
 */
interface Condition
{
    /**
     * Assert if the condition is correct
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Contracts\Resource|null $resource
     * @return bool
     */
    public function assert($action, Resource $resource = null);
}
