<?php
namespace BeatSwitch\Lock\Permissions;

/**
 * A restriction is placed when you deny a caller something
 */
class Restriction extends Permission
{
    /** @var string */
    const TYPE = 'restriction';

    /**
     * Validate a permission against the given params
     *
     * @param string $action
     * @param string|\BeatSwitch\Lock\Contracts\Resource $resource
     * @param int $resourceId
     * @return bool
     */
    public function isAllowed($action, $resource = null, $resourceId = null)
    {
        return ! $this->resolve($action, $resource, $resourceId);
    }

    /**
     * @return string
     */
    public function getPermissionType()
    {
        return self::TYPE;
    }
}
