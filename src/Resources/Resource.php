<?php
namespace BeatSwitch\Lock\Resources;

/**
 * A contract to identify a resource which can be used to set permissions on
 */
interface Resource
{
    /**
     * The string value for the type of resource
     *
     * @return string
     */
    public function getResourceType();

    /**
     * The main identifier for the resource
     *
     * @return int|null
     */
    public function getResourceId();
}
