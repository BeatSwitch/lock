<?php
namespace BeatSwitch\Lock\Roles;

final class SimpleRole implements Role
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $inherit;

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
