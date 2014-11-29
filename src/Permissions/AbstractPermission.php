<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Contracts\Permission;
use BeatSwitch\Lock\Contracts\Resource;

abstract class AbstractPermission
{
    /**
     * @var string
     */
    protected $action;

    /**
     * @var \BeatSwitch\Lock\Contracts\Resource|null
     */
    protected $resource;

    /**
     * @var \BeatSwitch\Lock\Contracts\Condition[]
     */
    protected $conditions;

    /**
     * @param string $action
     * @param \BeatSwitch\Lock\Contracts\Resource $resource
     * @param \BeatSwitch\Lock\Contracts\Condition[]
     */
    public function __construct($action, Resource $resource = null, array $conditions = array())
    {
        $this->action = $action;
        $this->resource = $resource;
        $this->conditions = $conditions;
    }

    /**
     * Determine if a permission exactly matches the current instance
     *
     * @param \BeatSwitch\Lock\Contracts\Permission $permission
     * @return bool
     */
    public function matchesPermission(Permission $permission)
    {
        return (
            $this instanceof $permission &&
            $this->action === $permission->getAction() && // Not using matchesAction to avoid the wildcard
            $this->matchesResource($permission->getResource())
        );
    }

    /**
     * Validate a permission against the given params.
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Contracts\Resource $resource
     * @return bool
     */
    protected function resolve($action, Resource $resource = null)
    {
        // If no resource was set for this permission we'll only need to check the action.
        if ($this->resource === null || $this->resource->getResourceType() === null) {
            return $this->matchesAction($action) && $this->resolveConditions();
        }

        return $this->matchesAction($action) && $this->matchesResource($resource) && $this->resolveConditions();
    }

    /**
     * Check if the given type is equal to the permission's type
     *
     * @param string $type
     * @return bool
     */
    protected function matchesType($type)
    {
        return $this->getType() === $type;
    }

    /**
     * Validate the action
     *
     * @param string $action
     * @return bool
     */
    protected function matchesAction($action)
    {
        return $this->action === $action || $this->action === 'all';
    }

    /**
     * Validate the resource
     *
     * @param \BeatSwitch\Lock\Contracts\Resource|null $resource
     * @return bool
     */
    protected function matchesResource($resource)
    {
        // If the permission's resource id is null then all resources with a specific ID are accepted.
        if ($this->getResourceId() === null) {
            return $this->getResourceType() === $resource->getResourceType();
        }

        // Otherwise make sure that we're matching a specific resource.
        return (
            $this->getResourceType() === $resource->getResourceType() &&
            $this->getResourceId() === $resource->getResourceId()
        );
    }

    /**
     * Check all the conditions and make sure they all return true
     *
     * @return bool
     */
    protected function resolveConditions()
    {
        foreach ($this->conditions as $condition) {
            if (! $condition->assert($this->action, $this->resource)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return \BeatSwitch\Lock\Contracts\Resource|null
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * The resource's type
     *
     * @return string|null
     */
    public function getResourceType()
    {
        return $this->resource ? $this->resource->getResourceType() : null;
    }

    /**
     * The resource's identifier
     *
     * @return int|null
     */
    public function getResourceId()
    {
        return $this->resource ? $this->resource->getResourceId() : null;
    }
}
