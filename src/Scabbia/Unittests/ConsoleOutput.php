<?php
/**
 * Scabbia2 PHP Framework Version 0.1
 * http://www.scabbiafw.com/
 * Licensed under the Apache License, Version 2.0
 */

namespace Scabbia\Unittests;

use Scabbia\Unittests\TestFixture;
use Scabbia\Unittests\IOutput;

/**
 * Scabbia\Unittests: ConsoleOutput Class
 *
 * A small unittest implementation which helps us during the development of
 * Scabbia2 PHP Framework's itself and related production code.
 *
 * @package Scabbia
 * @subpackage Unittests
 * @version 0.1
 */
class ConsoleOutput implements IOutput
{
    /**
     * Writes given message.
     *
     * @param $uHeading integer size
     * @param $uMessage string  message
     */
    public function writeHeader($uHeading, $uMessage)
    {
        if ($uHeading === 1) {
            $tChar = "=";
        } else {
            $tChar = "-";
        }

        echo "$uMessage\r\n", str_repeat($tChar, strlen($uMessage)), "\r\n";

        if ($uHeading === 1) {
            echo "\r\n";
        }
    }

    /**
     * Outputs the report to console.
     *
     * @param TestFixture $uFixture Target TestFixture instance
     */
    public function export(TestFixture $uFixture)
    {
        print_r($uFixture->testReport);
    }
}
