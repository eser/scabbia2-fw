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
use Scabbia\Unittests\IOutput;

/**
 * Scabbia\Unittests: HtmlOutput Class
 *
 * A small unittest implementation which helps us during the development of
 * Scabbia2 PHP Framework's itself and related production code.
 */
class HtmlOutput implements IOutput
{
    /**
     * Writes given message.
     *
     * @param $uHeading integer size
     * @param $uMessage string  message
     */
    public function writeHeader($uHeading, $uMessage)
    {
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
