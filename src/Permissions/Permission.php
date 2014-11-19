<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Contracts\Permission as PermissionContract;
use BeatSwitch\Lock\Contracts\Resource;

abstract class Permission implements PermissionContract
{
    /**
     * @var string
     */
    protected $action;

    /**
     * @var string|null
     */
    protected $resource;

    /**
     * @var int|null
     */
    protected $resourceId;

    /**
     * @param string $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     */
    public function __construct($action, $resource = null, $resourceId = null)
    {
        $this->action = $action;

        if ($resource instanceof Resource) {
            $this->resource = $resource->getResourceType();
            $this->resourceId = $resource->getResourceId();
        } else {
            $this->resource = $resource;
            $this->resourceId = $resourceId;
        }
    }

    /**
     * Determine if a permission exactly matches the current instance
     *
     * @param \BeatSwitch\Lock\Contracts\Permission $permission
     * @return bool
     */
    public function matchesPermission(PermissionContract $permission)
    {
        return (
            $this->getPermissionType() === $permission->getPermissionType() &&
            $this->action === $permission->getAction() &&
            $this->resource === $permission->getResource() &&
            $this->resourceId === $permission->getResourceId()
        );
    }

    /**
     * Validate a permission against the given params.
     *
     * @param string $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    protected function resolve($action, $resource = null, $resourceId = null)
    {
        // If no resource was set for this permission we'll only need to check the action.
        if ($this->resource === null && $this->resourceId === null) {
            return $this->matchesAction($action);
        }

        return $this->matchesAction($action) && $this->matchesResource($resource, $resourceId);
    }

    /**
     * Validate the action.
     *
     * @param string $action
     * @return bool
     */
    protected function matchesAction($action)
    {
        return $this->action === $action || $this->action === 'all';
    }

    /**
     * Validate the resource.
     *
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    protected function matchesResource($resource, $resourceId = null)
    {
        if ($resource instanceof Resource) {
            $resourceId = $resource->getResourceId();
            $resource = $resource->getResourceType();
        }

        // If no resource id was set for this permission we'll only need to check the resource type.
        if ($this->resourceId === null) {
            return $this->resource === $resource;
        }

        return $this->resource === $resource && $this->resourceId == $resourceId;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string|null
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return int|null
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }
}
