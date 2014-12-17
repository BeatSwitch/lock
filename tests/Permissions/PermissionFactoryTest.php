<?php
namespace tests\BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Permissions\PermissionFactory;
use stdClass;

class PermissionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    function it_can_map_an_array_of_data_to_permission_objects()
    {
        $data = [
            ['type' => 'privilege', 'action' => 'create', 'resource_type' => 'events', 'resource_id' => 1],
            ['type' => 'restriction', 'action' => 'update', 'resource_type' => 'comments', 'resource_id' => null],
        ];

        $result = PermissionFactory::createFromData($data);

        $this->assertContainsOnlyInstancesOf('BeatSwitch\Lock\Permissions\Permission', $result);
    }

    /** @test */
    function it_can_map_an_array_of_data_to_a_permission_object()
    {
        $data = ['type' => 'privilege', 'action' => 'update', 'resource_type' => 'comments', 'resource_id' => null];

        $result = PermissionFactory::createFromArray($data);

        $this->assertInstanceOf('BeatSwitch\Lock\Permissions\Privilege', $result);
    }

    /** @test */
    function it_can_map_an_array_of_objects_to_permission_objects()
    {
        $object = new stdClass();
        $object->type = 'privilege';
        $object->action = 'create';
        $object->resource_type = 'events';
        $object->resource_id = 1;

        $secondObject = new stdClass();
        $secondObject->type = 'restriction';
        $secondObject->action = 'update';
        $secondObject->resource_type = 'comments';
        $secondObject->resource_id = null;

        $result = PermissionFactory::createFromData([$object, $secondObject]);

        $this->assertContainsOnlyInstancesOf('BeatSwitch\Lock\Permissions\Permission', $result);
    }

    /** @test */
    function it_can_map_an_object_to_a_permission_object()
    {
        $object = new stdClass();
        $object->type = 'restriction';
        $object->action = 'update';
        $object->resource_type = 'comments';
        $object->resource_id = null;

        $result = PermissionFactory::createFromObject($object);

        $this->assertInstanceOf('BeatSwitch\Lock\Permissions\Restriction', $result);
    }

    /** @test */
    function it_throws_an_exception_for_an_incorrect_permission_type()
    {
        $this->setExpectedException(
            'BeatSwitch\Lock\Permissions\InvalidPermissionType',
            'The permission type you provided "something" is incorrect.'
        );

        $data = [['type' => 'something', 'action' => 'create', 'resource_type' => 'events', 'resource_id' => 1]];

        PermissionFactory::createFromData($data);
    }
}
