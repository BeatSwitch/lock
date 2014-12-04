<?php
namespace BeatSwitch\Lock\Resources;

final class SimpleResource implements Resource
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var int|null
     */
    private $id;

    /**
     * @param string $type
     * @param int|null $id
     */
    public function __construct($type, $id = null)
    {
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * The string value for the type of resource
     *
     * @return string
     */
    public function getResourceType()
    {
        return $this->type;
    }

    /**
     * The main identifier for the resource
     *
     * @return int|null
     */
    public function getResourceId()
    {
        return $this->id;
    }
}
