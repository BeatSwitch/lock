<?php
namespace BeatSwitch\Lock\Callers;

/**
 * A contract to identify a permission caller which can have permissions to do something
 *
 * A Caller is an object that can have permission to do something with a Resource. It's unique
 * thanks to its called id and its type identifier. By having a caller type, systems can store permissions for
 * different types of callers like users and organisations.
 */
interface Caller
{
    /**
     * The type of caller
     *
     * @return string
     */
    public function getCallerType();

    /**
     * The unique ID to identify the caller with
     *
     * @return int
     */
    public function getCallerId();

    /**
     * The caller's roles
     *
     * @return array
     */
    public function getCallerRoles();
}
