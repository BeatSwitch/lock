<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Resources\SimpleResource;

class PermissionFactory
{
    /**
     * Maps an array of permission data to Permission objects
     *
     * @param array $permissions
     * @return \BeatSwitch\Lock\Permissions\Permission[]
     */
    public static function createFromData($permissions)
    {
        return array_map(function ($permission) {
            if (is_array($permission)) {
                return PermissionFactory::createFromArray($permission);
            } else {
                return PermissionFactory::createFromObject($permission);
            }
        }, $permissions);
    }

    /**
     * Maps an data array to a permission object
     *
     * @param array $permission
     * @return \BeatSwitch\Lock\Permissions\Permission
     * @throws \BeatSwitch\Lock\Permissions\InvalidPermissionType
     */
    public static function createFromArray(array $permission)
    {
        $type = $permission['type'];

        // Make sure the id is typecast to an integer.
        $id = ! is_null($permission['resource_id']) ? (int) $permission['resource_id'] : null;

        if ($type === Privilege::TYPE) {
            return new Privilege(
                $permission['action'],
                new SimpleResource($permission['resource_type'], $id)
            );
        } elseif ($type === Restriction::TYPE) {
            return new Restriction(
                $permission['action'],
                new SimpleResource($permission['resource_type'], $id)
            );
        } else {
            throw new InvalidPermissionType("The permission type you provided \"$type\" is incorrect.");
        }
    }

    /**
     * Maps an data object to a permission object
     *
     * @param object $permission
     * @return \BeatSwitch\Lock\Permissions\Permission[]
     * @throws \BeatSwitch\Lock\Permissions\InvalidPermissionType
     */
    public static function createFromObject($permission)
    {
        // Make sure the id is typecast to an integer.
        $id = ! is_null($permission->resource_id) ? (int) $permission->resource_id : null;

        if ($permission->type === Privilege::TYPE) {
            return new Privilege(
                $permission->action,
                new SimpleResource($permission->resource_type, $id)
            );
        } elseif ($permission->type === Restriction::TYPE) {
            return new Restriction(
                $permission->action,
                new SimpleResource($permission->resource_type, $id)
            );
        } else {
            throw new InvalidPermissionType("The permission type you provided \"{$permission->type}\" is incorrect.");
        }
    }
}
