<?php
namespace BeatSwitch\Lock;

/**
 * This trait can be used on objects which extend the Caller or Role contract.
 * After setting the Lock instance with the setLock method, the object receives
 * the ability to call the public api from the lock instance onto itself.
 */
trait LockAware
{
    /**
     * The current object's lock instance
     *
     * @var \BeatSwitch\Lock\Lock
     */
    private $lock;

    /**
     * Determine if one or more actions are allowed
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
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
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
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
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param int $resourceId
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    public function allow($action, $resource = null, $resourceId = null, $conditions = [])
    {
        $this->assertLockInstanceIsSet();

        $this->lock->allow($action, $resource, $resourceId, $conditions);
    }

    /**
     * Deny a caller from doing something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param int $resourceId
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    public function deny($action, $resource = null, $resourceId = null, $conditions = [])
    {
        $this->assertLockInstanceIsSet();

        $this->lock->deny($action, $resource, $resourceId, $conditions);
    }

    /**
     * Change the value for a permission
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param int $resourceId
     */
    public function toggle($action, $resource = null, $resourceId = null)
    {
        $this->assertLockInstanceIsSet();

        $this->lock->toggle($action, $resource, $resourceId);
    }

    /**
     * Returns the allowed ids which match the given action and resource type
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resourceType
     * @return array
     */
    public function allowed($action, $resourceType)
    {
        $this->assertLockInstanceIsSet();

        return $this->lock->allowed($action, $resourceType);
    }

    /**
     * Returns the denied ids which match the given action and resource type
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resourceType
     * @return array
     */
    public function denied($action, $resourceType)
    {
        $this->assertLockInstanceIsSet();

        return $this->lock->denied($action, $resourceType);
    }

    /**
     * Clear a given permission on a subject
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Resources\Resource $resource
     * @param int $resourceId
     */
    public function clear($action, $resource = null, $resourceId = null)
    {
        $this->assertLockInstanceIsSet();

        $this->lock->clear($action, $resource, $resourceId);
    }

    /**
     * Sets the lock instance for this caller
     *
     * @param \BeatSwitch\Lock\Lock $lock
     * @throws \BeatSwitch\Lock\InvalidLockInstance
     */
    public function setLock(Lock $lock)
    {
        // Make sure that the subject from the given lock instance is this object.
        if ($lock->getSubject() !== $this) {
            throw new InvalidLockInstance('Invalid Lock instance given for current object.');
        }

        $this->lock = $lock;
    }

    /**
     * Makes sure that a valid lock instance is set before an api method is called
     *
     * @throws \BeatSwitch\Lock\LockInstanceNotSet
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
