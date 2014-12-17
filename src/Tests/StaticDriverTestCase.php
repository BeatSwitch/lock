<?php
namespace BeatSwitch\Lock\Tests;

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

        $lock->allow('upload', 'files', null, new TrueConditionStub());
        $lock->allow('upload', 'photos', null, new FalseConditionStub());

        $this->assertTrue($lock->can('upload', 'files'));
        $this->assertFalse($lock->can('upload', 'photos'));
    }

    /** @test */
    final function it_can_work_with_multiple_conditions()
    {
        $lock = $this->getCallerLock();

        $lock->allow('upload', 'files', null, [new FalseConditionStub(), new TrueConditionStub()]);

        $this->assertFalse($lock->can('upload', 'files'));
    }

    /** @test */
    final function it_can_work_with_callback_conditions()
    {
        $lock = $this->getCallerLock();

        $lock->allow('upload', 'files', null, function () {
            return true;
        });
        $lock->allow('upload', 'photos', null, function () {
            return false;
        });

        $this->assertTrue($lock->can('upload', 'files'));
        $this->assertFalse($lock->can('upload', 'photos'));
    }
}
