<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Resource;

class PermissionFactory
{
    /**
     * Maps an array of permission data to Permission objects
     *
     * @param array $permissions
     * @return \BeatSwitch\Lock\Permissions\Permission[]
     */
    public static function createFromArray($permissions)
    {
        return array_map(function ($permission) {
            $type = $permission['type'];

            if ($type === Privilege::TYPE) {
                return new Privilege(
                    $permission['action'],
                    new Resource($permission['resource'], $permission['resource_id'])
                );
            } elseif ($type === Restriction::TYPE) {
                return new Restriction(
                    $permission['action'],
                    new Resource($permission['resource'], $permission['resource_id'])
                );
            } else {
                throw new InvalidPermissionType("The permission type you provided \"$type\" is incorrect.");
            }
        }, $permissions);
    }
}
