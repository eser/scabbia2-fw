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

namespace Scabbia\Tests;

use Scabbia\Tests\IOutput;

/**
 * Scabbia\Tests: ConsoleOutput Class
 *
 * A small unit test implementation which helps us during the development of
 * Scabbia2 PHP Framework's itself and related production code.
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
     * @param array $uReport Target report will be printed
     */
    public function export(array $uReport)
    {
        print_r($uReport);
    }
}
