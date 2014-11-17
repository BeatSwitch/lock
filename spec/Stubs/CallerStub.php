<?php
namespace spec\BeatSwitch\Lock\Stubs;

use BeatSwitch\Lock\Contracts\Caller;

class CallerStub implements Caller
{
    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $id;

    /**
     * @param string $type
     * @param int $id
     */
    public function __construct($type, $id)
    {
        $this->type = $type;
        $this->id = $id;
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
}
