<?php

use Scabbia\Unittests\TestFixture;

/**
 * Class SampleTest
 *
 * Just to present how unit-testing works.
 */
class SampleTest extends TestFixture
{
    /**
     * The method always fails
     */
    public function firstFailingCondition()
    {
        $this->assertTrue(true === true, 'no way!');
    }
}
