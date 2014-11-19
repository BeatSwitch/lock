<?php
namespace BeatSwitch\Lock;

class ActionAlias
{
    /**
     * The alias name
     *
     * @var string
     */
    protected $name;

    /**
     * The actions for this alias
     *
     * @var array
     */
    protected $actions;

    /**
     * @param string $name
     * @param string|array $actions
     */
    public function __construct($name, $actions)
    {
        $this->name = $name;
        $this->actions = (array) $actions;
    }

    /**
     * Determine if the given action is registered to this alias
     *
     * @param string $action
     * @return bool
     */
    public function hasAction($action)
    {
        return in_array($action, $this->actions);
    }
}
