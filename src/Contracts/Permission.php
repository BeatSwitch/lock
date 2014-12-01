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
     * @param \BeatSwitch\Lock\Contracts\Resource|null $resource
     * @return bool
     */
    public function isAllowed($action, Resource $resource = null);

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
     * @return \BeatSwitch\Lock\Contracts\Resource|null
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
     * @return id|null
     */
    public function getResourceId();
}
 