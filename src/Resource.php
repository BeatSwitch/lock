<?php
namespace BeatSwitch\Lock;

class Resource implements Contracts\Resource
{
    /**
     * @var string|null
     */
    private $resourceType;

    /**
     * @var int|null
     */
    private $resourceId;

    /**
     * @param string|null $resourceType
     * @param int|null $resourceId
     */
    public function __construct($resourceType = null, $resourceId = null)
    {
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
    }

    /**
     * The string value for the type of resource
     *
     * @return string|null
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * The main identifier for the resource
     *
     * @return int|null
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }
}
