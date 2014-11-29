<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Contracts\Permission;
use BeatSwitch\Lock\Contracts\Resource;

/**
 * A privilege is placed when you allow a caller something
 */
class Privilege extends AbstractPermission implements Permission
{
    /** @var string */
    const TYPE = 'privilege';

    /**
     * Validate a permission against the given params
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Contracts\Resource $resource
     * @return bool
     */
    public function isAllowed($action, Resource $resource = null)
    {
        return $this->resolve($action, $resource);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }
}
