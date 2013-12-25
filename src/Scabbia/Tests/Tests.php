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

    /**
     * Starts the code coverage.
     */
    public static function coverageStart()
    {
        xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    }

    /**
     * Stops the code coverage.
     *
     * @return array results
     */
    public static function coverageStop()
    {
        $tCoverageData = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();

        $tFinal = [
            "files" => [],
            "total" => [ "coveredLines" => 0, "totalLines" => 0 ]
        ];

        foreach ($tCoverageData as $tPath => $tLines) {
            $tFileCoverage = [
                "path"         => $tPath,
                "coveredLines" => array_keys($tLines),
                "totalLines"   => self::getFileLineCount($tPath)
            ];

            $tFinal["files"][] = $tFileCoverage;
            $tFinal["total"]["coveredLines"] += count($tFileCoverage["coveredLines"]);
            $tFinal["total"]["totalLines"] += $tFileCoverage["totalLines"];
        }

        $tFinal["total"]["percentage"] = ($tFinal["total"]["coveredLines"] * 100) / $tFinal["total"]["totalLines"];

        return $tFinal;
    }

    /**
     * Gets the number of lines of given file.
     *
     * @param string $uPath the path
     *
     * @return int|bool line count
     *
     * @see cloned from Scabbia\Framework\Io::getFileLineCount
     */
    protected static function getFileLineCount($uPath)
    {
        $tLineCount = 1;

        $tFileHandle = @fopen($uPath, "r");
        if ($tFileHandle === false) {
            return false;
        }

        while (!feof($tFileHandle)){
            $tLineCount += substr_count(fgets($tFileHandle, 4096), "\n");
        }

        fclose($tFileHandle);

        return $tLineCount;
    }
}
