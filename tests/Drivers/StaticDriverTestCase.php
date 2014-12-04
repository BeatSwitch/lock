<?php
namespace tests\BeatSwitch\Lock\Drivers;

use stubs\BeatSwitch\Lock\FalseConditionStub;
use stubs\BeatSwitch\Lock\TrueConditionStub;

/**
 * The StaticDriverTestCase can be used to test static drivers
 *
 * This is a separate class because atm persistent drivers cannot handle conditions.
 */
abstract class StaticDriverTestCase extends PersistentDriverTestCase
{
    /** @test */
    final function it_can_work_with_conditions()
    {
        $lock = $this->getCallerLock();

        $lock->allow('upload', 'files', null, [new TrueConditionStub()]);
        $lock->allow('upload', 'photos', null, [new FalseConditionStub()]);

        $this->assertTrue($lock->can('upload', 'files'));
        $this->assertFalse($lock->can('upload', 'photos'));
    }
}
