<?php
namespace BeatSwitch\Lock\Tests\Stubs;

use BeatSwitch\Lock\Contracts\Resource as PermissionResource;

class Event implements PermissionResource
{
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getResourceType()
    {
        return 'events';
    }

    public function getResourceId()
    {
        return $this->id;
    }
}
