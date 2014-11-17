<?php
namespace BeatSwitch\Lock\Drivers;

use BeatSwitch\Lock\Contracts\Caller;
use BeatSwitch\Lock\Contracts\Driver;
use BeatSwitch\Lock\Contracts\Permission;

/**
 * The store and delete methods on this driver do nothing
 * and thus only the get and has methods can be used.
 */
abstract class ReadOnlyDriver extends AbstractDriver implements Driver
{
    /**
     * Stores a new permission into the driver for a caller
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return void
     */
    final public function storePermission(Caller $caller, Permission $permission)
    {
    }

    /**
     * Removes a permission from the driver for a caller
     *
     * @param \BeatSwitch\Lock\Contracts\Caller $caller
     * @param \BeatSwitch\Lock\Contracts\Permission
     * @return void
     */
    final public function removePermission(Caller $caller, Permission $permission)
    {
    }
}
