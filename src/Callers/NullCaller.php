<?php
namespace BeatSwitch\Lock\Callers;

/**
 * A NullCaller represents a non-existing caller
 *
 * The NullCaller can be used in contexts where there isn't a caller to instantiate the Lock class with. Since it does
 * not have any roles or permissions, it always returns false on checks to see if it has permission to do something.
 * This can be useful in situations where you are handling guest users, public parts of your api or testing.
 */
class NullCaller implements Caller
{
    /**
     * @return null
     */
    public function getCallerType()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getCallerId()
    {
        return null;
    }

    /**
     * The caller's roles
     *
     * @return array
     */
    public function getCallerRoles()
    {
        return [];
    }
}
