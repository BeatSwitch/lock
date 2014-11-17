<?php
namespace BeatSwitch\Lock\Permissions;

/**
 * A privilege is placed when you allow a caller something
 */
class Privilege extends AbstractPermission
{
    /** @var string */
    const TYPE = 'privilege';

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
        return $this->resolve($action, $resource, $resourceId);
    }

    /**
     * @return string
     */
    public function getPermissionType()
    {
        return self::TYPE;
    }
}
