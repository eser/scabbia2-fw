<?php
/**
 * Scabbia2 PHP Framework
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        http://github.com/scabbiafw/scabbia2 for the canonical source repository
 * @copyright   2010-2013 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Tests;

use Scabbia\Tests\ConsoleTestOutput;
use Scabbia\Tests\HtmlTestOutput;

/**
 * A small test implementation which helps us during the development of
 * Scabbia2 PHP Framework's itself and related production code.
 *
 * @package     Scabbia\Tests
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class Tests
{
    /**
     * Runs given unit tests.
     *
     * @param $uTestClasses array   Set of unit test classes.
     *
     * @return int exit code
     */
    public static function runUnitTests(array $uTestClasses)
    {
        if (PHP_SAPI === "cli") {
            $tOutput = new ConsoleTestOutput();
        } else {
            $tOutput = new HtmlTestOutput();
        }

        $tIsEverFailed = false;

        $tOutput->writeHeader(1, "Unit Tests");

        /** @type string $tTestClass */
        foreach ($uTestClasses as $tTestClass) {
            $tOutput->writeHeader(2, $tTestClass);

            $tInstance = new $tTestClass ();
            $tInstance->test();

            if ($tInstance->isFailed) {
                $tIsEverFailed = true;
            }

            $tOutput->writeTestReport($tInstance->testReport);
        }

        if ($tIsEverFailed) {
            return 1;
        }

        return 0;
    }
}
