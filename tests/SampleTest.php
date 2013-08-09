<?php
/**
 * Scabbia2 PHP Framework
 * http://www.scabbiafw.com/
 *
 * Licensed under the Apache License, Version 2.0
 *
 * @link        http://github.com/scabbiafw/scabbia2 for the canonical source repository
 * @copyright   Copyright (c) 2010-2013 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace ScabbiaTests;

use Scabbia\Unittests\TestFixture;

/**
 * ScabbiaTests: SampleTest Class
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
