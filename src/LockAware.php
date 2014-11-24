<?php
namespace BeatSwitch\Lock;

use BeatSwitch\Lock\Exceptions\InvalidCaller;
use BeatSwitch\Lock\Exceptions\LockInstanceNotSet;

/**
 * This trait can be used on objects which extend the Caller contract. After
 * setting the Lock instance with the setLock method, the caller receives
 * the ability to call the public api from the lock instance onto itself.
 */
trait LockAware
{
    /**
     * The current caller's lock instance
     *
     * @var \BeatSwitch\Lock\Lock
     */
    protected $lock;

    /**
     * Determine if one or more actions are allowed
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    public function can($action, $resource = null, $resourceId = null)
    {
        $this->assertLockInstanceIsSet();

        return $this->lock->can($action, $resource, $resourceId);
    }

    /**
     * Determine if an action isn't allowed
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    public function cannot($action, $resource = null, $resourceId = null)
    {
        $this->assertLockInstanceIsSet();

        return $this->lock->cannot($action, $resource, $resourceId);
    }

    /**
     * Give a caller permission to do something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @param \BeatSwitch\Lock\Contracts\Condition[] $conditions
     */
    public function allow($action, $resource = null, $resourceId = null, array $conditions = array())
    {
        $this->assertLockInstanceIsSet();

        $this->lock->allow($action, $resource, $resourceId, $conditions);
    }

    /**
     * Deny a caller from doing something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @param \BeatSwitch\Lock\Contracts\Condition[] $conditions
     */
    public function deny($action, $resource = null, $resourceId = null, array $conditions = array())
    {
        $this->assertLockInstanceIsSet();

        $this->lock->deny($action, $resource, $resourceId, $conditions);
    }

    /**
     * Change the value for a permission
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     */
    public function toggle($action, $resource = null, $resourceId = null)
    {
        $this->assertLockInstanceIsSet();

        $this->lock->toggle($action, $resource, $resourceId);
    }

    /**
     * Sets the lock instance for this caller
     *
     * @param \BeatSwitch\Lock\Lock $lock
     * @throws \BeatSwitch\Lock\Exceptions\InvalidCaller
     */
    public function setLock(Lock $lock)
    {
        // Make sure that the caller from the given lock instance is this caller.
        if ($lock->getCaller() !== $this) {
            throw new InvalidCaller('The caller from the given lock instance is different from the current caller.');
        }

        $this->lock = $lock;
    }

    /**
     * Makes sure that a valid lock instance is set before an api method is called
     *
     * @throws \BeatSwitch\Lock\Exceptions\LockInstanceNotSet
     */
    private function assertLockInstanceIsSet()
    {
        if (! $this->lock instanceof Lock) {
            throw new LockInstanceNotSet(
                'Please set a valid lock instance on this class before attempting to use it.'
            );
        }
    }
}
