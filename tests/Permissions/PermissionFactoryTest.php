<?php
namespace tests\BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Permissions\PermissionFactory;

class PermissionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    function it_can_map_an_array_of_data_to_permission_objects()
    {
        $data = [
            ['type' => 'privilege', 'action' => 'create', 'resource' => 'events', 'resource_id' => 1],
            ['type' => 'restriction', 'action' => 'update', 'resource' => 'comments', 'resource_id' => null],
        ];

        $result = PermissionFactory::createFromArray($data);

        $this->assertContainsOnlyInstancesOf('BeatSwitch\Lock\Contracts\Permission', $result);
    }

    /** @test */
    function it_throws_an_exception_for_an_incorrect_permission_type()
    {
        $this->setExpectedException(
            'BeatSwitch\Lock\Exceptions\InvalidPermissionType',
            'The permission type you provided "something" is incorrect.'
        );

        $data = [['type' => 'something', 'action' => 'create', 'resource' => 'events', 'resource_id' => 1]];

        PermissionFactory::createFromArray($data);
    }
}
