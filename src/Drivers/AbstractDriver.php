<?php
namespace BeatSwitch\Lock\Drivers;

use BeatSwitch\Lock\Exceptions\InvalidPermission;
use BeatSwitch\Lock\Permissions\Privilege;
use BeatSwitch\Lock\Permissions\Restriction;

abstract class AbstractDriver
{
    /**
     * Maps an array of permission data to Permission objects
     *
     * @param array $permissions
     * @return \BeatSwitch\Lock\Contracts\Permission[]
     */
    protected function mapPermissions(array $permissions)
    {
        return array_map(function ($permission) {
            $type = $permission['type'];

            if ($type === Privilege::TYPE) {
                return new Privilege($permission['action'], $permission['resource'], $permission['resource_id']);
            } elseif ($type === Restriction::TYPE) {
                return new Restriction($permission['action'], $permission['resource'], $permission['resource_id']);
            } else {
                throw new InvalidPermission("The permission type you provided ($type) is incorrect.");
            }
        }, $permissions);
    }
}
 