<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Lock;
use BeatSwitch\Lock\Resources\Resource;

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
     * @param \BeatSwitch\Lock\Lock $lock
     * @param string $action
     * @param \BeatSwitch\Lock\Resources\Resource|null $resource
     * @return bool
     */
    public function isAllowed(Lock $lock, $action, Resource $resource = null)
    {
        return $this->resolve($lock, $action, $resource);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }
}
