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
        $this->lock->allow('upload', 'files', null, [new TrueConditionStub()]);
        $this->lock->allow('upload', 'photos', null, [new FalseConditionStub()]);

        $this->assertTrue($this->lock->can('upload', 'files'));
        $this->assertFalse($this->lock->can('upload', 'photos'));
    }
}
