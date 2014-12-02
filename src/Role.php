<?php
namespace BeatSwitch\Lock;

class Role implements Roles\Role
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $inherit;

    /**
     * @param string $name
     * @param string $inherit
     */
    public function __construct($name, $inherit = null)
    {
        $this->name = $name;
        $this->inherit = $inherit;
    }

    /**
     * The name for this role instance
     *
     * @return string
     */
    public function getRoleName()
    {
        return $this->name;
    }

    /**
     * The name for the role from which this role inherits permissions
     *
     * @return string|null
     */
    public function getInheritedRole()
    {
        return $this->inherit;
    }
}
