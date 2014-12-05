<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Lock;
use BeatSwitch\Lock\Resources\Resource;

/**
 * A contract to define a permission rule, either a restriction or a privilege
 */
interface Permission
{
    /**
     * Validate a permission against the given params
     *
     * @param \BeatSwitch\Lock\Lock $lock
     * @param string $action
     * @param \BeatSwitch\Lock\Resources\Resource|null $resource
     * @return bool
     */
    public function isAllowed(Lock $lock, $action, Resource $resource = null);

    /**
     * Determine if a permission exactly matches the current instance
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     * @return bool
     */
    public function matchesPermission(Permission $permission);

    /**
     * The type of permission, either "privilege" or "restriction"
     *
     * @return string
     */
    public function getType();

    /**
     * The action the permission is set for
     *
     * @return string
     */
    public function getAction();

    /**
     * The optional resource an action should be checked on
     *
     * @return \BeatSwitch\Lock\Resources\Resource|null
     */
    public function getResource();

    /**
     * The resource's type
     *
     * @return string|null
     */
    public function getResourceType();

    /**
     * The resource's identifier
     *
     * @return int|null
     */
    public function getResourceId();
}
 