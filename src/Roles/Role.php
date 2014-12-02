<?php
namespace BeatSwitch\Lock\Roles;

interface Role
{
    /**
     * The name for this role instance
     *
     * @return string
     */
    public function getRoleName();

    /**
     * The name for the role from which this role inherits permissions
     *
     * @return string|null
     */
    public function getInheritedRole();
}
