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

namespace Scabbia\Unittests;

use Scabbia\Unittests\TestFixture;

/**
 * Scabbia\Unittests: IOutput Interface
 *
 * A small unittest implementation which helps us during the development of
 * Scabbia2 PHP Framework's itself and related production code.
 */
interface IOutput
{
    /**
     * Writes given message.
     *
     * @param $uHeading integer size
     * @param $uMessage string  message
     */
    public function writeHeader($uHeading, $uMessage);

    /**
     * Outputs the report in specified representation.
     *
     * @param TestFixture $uFixture Target TestFixture instance
     */
    public function export(TestFixture $uFixture);
}
