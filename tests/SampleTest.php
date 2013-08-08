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
     * The method always passes
     */
    public function firstFailProofCondition()
    {
        $this->assertFalse(true === true, 'no way!');
    }
}
