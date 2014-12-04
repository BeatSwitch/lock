<?php
namespace BeatSwitch\Lock\Callers;

use BeatSwitch\Lock\LockAware;

final class SimpleCaller implements Caller
{
    use LockAware;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
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
     * @return int
     */
    public function getCallerId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getCallerRoles()
    {
        return $this->roles;
    }
}
