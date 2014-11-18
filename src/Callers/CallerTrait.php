<?php
namespace BeatSwitch\Lock\Callers;

use BeatSwitch\Lock\Exceptions\InvalidLockCaller;
use BeatSwitch\Lock\Exceptions\LockInstanceNotSet;
use BeatSwitch\Lock\Lock;
use BeatSwitch\Lock\Manager;

/**
 * This trait can be used on objects which extend the Caller contract. After
 * setting the Lock instance with the setLock method, the caller receives
 * the ability to call the public api from the lock instance onto itself.
 */
trait CallerTrait
{
    /**
     * The current caller's lock instance
     *
     * @var \BeatSwitch\Lock\Lock
     */
    protected $lock;

    /**
     * Determine if an action is allowed
     *
     * @param string $action
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
     * @param string $action
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
     * Adds a permission for a caller
     *
     * @param string $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     */
    public function allow($action, $resource = null, $resourceId = null)
    {
        $this->assertLockInstanceIsSet();

        $this->lock->allow($action, $resource, $resourceId);
    }

    /**
     * Removes a permission from a caller
     *
     * @param string $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     */
    public function deny($action, $resource = null, $resourceId = null)
    {
        $this->assertLockInstanceIsSet();

        $this->lock->deny($action, $resource, $resourceId);
    }

    /**
     * Change the value for a permission
     *
     * @param string $action
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
     * @param \BeatSwitch\Lock\Manager $manager
     */
    public function setLock(Manager $manager)
    {
        $this->lock = $manager->caller($this);
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
