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
 * Scabbia\Unittests: HtmlOutput Class
 *
 * A small unittest implementation which helps us during the development of
 * Scabbia2 PHP Framework's itself and related production code.
 *
 * @package Scabbia
 * @subpackage Unittests
 * @version 0.1
 */
class HtmlOutput implements IOutput
{
    /**
     * Writes given message.
     *
     * @param $uHeading integer size
     * @param $uMessage string  message
     */
    public function writeHeader($uHeading, $uMessage) {
        echo "<h{$uHeading}>$uMessage</h{$uHeading}>";
    }

    /**
     * Outputs the report in HTML representation.
     *
     * @param TestFixture $uFixture Target TestFixture instance
     */
    public function export(TestFixture $uFixture)
    {
        foreach ($uFixture->testReport as $tEntryKey => $tEntry) {
            echo "<p>";
            echo "<strong>{$tEntryKey}:</strong><br />";
            echo "<ul>";

            $tPassed = true;
            foreach ($tEntry as $tTest) {
                if ($tTest['failed']) {
                    $tPassed = false;
                    echo "<li>";
                    echo "<span style=\"color: red;\">{$tTest['operation']}</span>";
                    if ($tTest['message'] !== null) {
                        echo ": {$tTest['message']}";
                    }
                    echo "</li>";
                } else {
                    echo "<li>";
                    echo "<span style=\"color: green;\">{$tTest['operation']}</span>";
                    if ($tTest['message'] !== null) {
                        echo ": {$tTest['message']}";
                    }
                    echo "</li>";
                }
            }

            echo "</ul>";

            if (!$tPassed) {
                echo "<span style=\"color: red;\">FAILED</span>";
            } else {
                echo "<span style=\"color: green;\">PASSED</span>";
            }

            echo "</p>";
        }
    }
}
