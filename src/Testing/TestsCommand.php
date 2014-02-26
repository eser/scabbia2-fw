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

namespace Scabbia\Testing;

use Scabbia\Testing\Testing;
use Scabbia\Output\IOutput;

/**
 * Command class for "php scabbia tests"
 *
 * @package     Scabbia\Testing
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class TestsCommand
{
    /**
     * Entry point for the command
     *
     * @param array   $uParameters command parameters
     * @param mixed   $uConfig     command configuration
     * @param IOutput $uOutput     output
     *
     * @return int exit code
     */
    public static function tests(array $uParameters, $uConfig, IOutput $uOutput)
    {
        Testing::coverageStart();
        $tExitCode = Testing::runUnitTests($uConfig["fixtures"], $uOutput);
        $tCoverageReport = Testing::coverageStop();

        $tCoverage = round($tCoverageReport["total"]["percentage"], 2);
        $uOutput->writeColor("green", "Code Coverage = {$tCoverage}%");
        $uOutput->writeColor("yellow", "done.");

        return $tExitCode;
    }
}
