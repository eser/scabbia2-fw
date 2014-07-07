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

namespace Scabbia\Framework\Tasks;

use Scabbia\Framework\Core;
use Scabbia\Interfaces\IInterface;
use Scabbia\Tasks\TaskBase;

/**
 * Task class for "php scabbia help"
 *
 * @package     Scabbia\Framework\Tasks
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class HelpTask extends TaskBase
{
    /**
     * Registers the tasks itself to an interpreter instance
     *
     * @param Interpreter $uInterpreter interpreter to be registered at
     *
     * @return void
     */
    public static function registerToInterpreter(Interpreter $uInterpreter)
    {
        $uInterpreter->addCommand(
            "help",
            "Displays this help",
            []
        );
    }

    /**
     * Initializes the serve task
     *
     * @param mixed      $uConfig    configuration
     * @param IInterface $uInterface interface class
     *
     * @return HelpTask
     */
    public function __construct($uConfig, $uInterface)
    {
        parent::__construct($uConfig, $uInterface);
    }

    /**
     * Executes the task
     *
     * @param array $uParameters parameters
     *
     * @return int exit code
     */
    public function executeTask(array $uParameters)
    {
        // TODO call interpreter->help();
        return 0;
    }
}
