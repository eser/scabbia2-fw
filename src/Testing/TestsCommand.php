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

use Scabbia\Commands\CommandBase;
use Scabbia\Testing\Testing;

/**
 * Command class for "php scabbia tests"
 *
 * @package     Scabbia\Testing
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class TestsCommand extends CommandBase
{
    /**
     * Initializes the tests command
     *
     * @param mixed      $uConfig    configuration
     * @param IInterface $uInterface interface class
     *
     * @return TestsCommand
     */
    public function __construct($uConfig, $uInterface)
    {
        parent::__construct($uConfig, $uInterface);
    }

    /**
     * Executes the command
     *
     * @param array $uParameters parameters
     *
     * @return int exit code
     */
    public function executeCommand(array $uParameters)
    {
        Testing::coverageStart();
        $tExitCode = Testing::runUnitTests($this->config["fixtures"], $this->interface);
        $tCoverageReport = Testing::coverageStop();

        if ($tCoverageReport !== null) {
            $tCoverage = round($tCoverageReport["total"]["percentage"], 2) . "%";
        } else {
            $tCoverage = "unknown";
        }

        $this->interface->writeColor("green", "Code Coverage = {$tCoverage}");
        $this->interface->writeColor("yellow", "done.");

        return $tExitCode;
    }
}
