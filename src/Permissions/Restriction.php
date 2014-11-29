<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Contracts\Permission;
use BeatSwitch\Lock\Contracts\Resource;

/**
 * A restriction is placed when you deny a caller something
 */
class Restriction extends AbstractPermission implements Permission
{
    /** @var string */
    const TYPE = 'restriction';

    /**
     * Validate a permission against the given params
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Contracts\Resource $resource
     * @return bool
     */
    public function isAllowed($action, Resource $resource = null)
    {
        return ! $this->resolve($action, $resource);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }
}
