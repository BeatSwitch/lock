<?php
namespace spec\BeatSwitch\Lock\Stubs;

use BeatSwitch\Lock\Contracts\Resource;

class ResourceStub implements Resource
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
    public function getResourceType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getResourceId()
    {
        return $this->id;
    }
}
