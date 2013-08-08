<?php
/**
 * Scabbia2 PHP Framework Version 0.1
 * http://www.scabbiafw.com/
 * Licensed under the Apache License, Version 2.0
 */

namespace Scabbia\Unittests;

use Scabbia\Unittests\TestFixture;

/**
 * Scabbia\Unittests: IOutput Interface
 *
 * A small unittest implementation which helps us during the development of
 * Scabbia2 PHP Framework's itself and related production code.
 *
 * @package Scabbia
 * @subpackage Unittests
 * @version 0.1
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
