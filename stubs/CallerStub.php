<?php
namespace stubs\BeatSwitch\Lock;

use BeatSwitch\Lock\Contracts\Caller;
use BeatSwitch\Lock\LockAware;

class CallerStub implements Caller
{
    use LockAware;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $roles;

    /**
     * @param string $type
     * @param int $id
     * @param array $roles
     */
    public function __construct($type, $id, array $roles = [])
    {
        $this->type = $type;
        $this->id = $id;
        $this->roles = $roles;
    }
    /**
     * @return string
     */
    public function getCallerType()
    {
        return $this->type;
    }

    /**
     * The main identifier for the resource
     *
     * @return int
     */
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
        return $this->roles;
    }
}
