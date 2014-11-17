<?php
namespace BeatSwitch\Lock\Contracts;

/**
 * A contract to define a permission rule, either a restriction or a privilege
 */
interface Permission
{
    /**
     * Validate a permission against the given params
     *
     * @param string $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    public function isAllowed($action, $resource = null, $resourceId = null);

    /**
     * Determine if a permission exactly matches the current instance
     *
     * @param \BeatSwitch\Lock\Contracts\Permission $permission
     * @return bool
     */
    public function matchesPermission(Permission $permission);

    /**
     * The type of permission, either "privilege" or "restriction"
     *
     * @return string
     */
    public function getPermissionType();

    /**
     * @return string
     */
    public function getAction();

    /**
     * @return string|null
     */
    public function getResource();

    /**
     * @return int|null
     */
    public function getResourceId();
}
 