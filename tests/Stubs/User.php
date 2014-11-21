<?php
namespace BeatSwitch\Lock\Tests\Stubs;

use BeatSwitch\Lock\Callers\CallerTrait;
use BeatSwitch\Lock\Contracts\Caller as PermissionCaller;

class User implements PermissionCaller
{
    use CallerTrait;

    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getCallerType()
    {
        return 'users';
    }

    public function getCallerId()
    {
        return $this->id;
    }

    /**
     * The caller's roles
     *
     * @return array
     */
    public function getCallerRoles()
    {
        return ['editor'];
    }
}
