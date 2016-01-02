<?php
/**
 * Scabbia2 PHP Framework Code
 * https://github.com/eserozvataf/scabbia2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/eserozvataf/scabbia2-fw for the canonical source repository
 * @copyright   2010-2016 Eser Ozvataf. (http://eser.ozvataf.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Framework\Tasks;

use Scabbia\Framework\Core;
use Scabbia\Objects\CommandInterpreter;
use Scabbia\Tasks\TaskBase;

/**
 * Task class for "php scabbia serve"
 *
 * @package     Scabbia\Framework\Tasks
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 *
 * @scabbia-task serve
 */
class ServeTask extends TaskBase
{
    /**
     * Registers the tasks itself to a command interpreter instance
     *
     * @param CommandInterpreter $uCommandInterpreter interpreter to be registered at
     *
     * @return void
     */
    public static function registerToCommandInterpreter(CommandInterpreter $uCommandInterpreter)
    {
        $uCommandInterpreter->addCommand(
            "serve",
            "Runs built-in PHP server",
            [
                // type, name, description
                [Console::OPTION, "--host", "Binding host address"],
                [Console::OPTION, "--port", "Binding port number"]
            ]
        );
    }

    /**
     * Initializes the serve task
     *
     * @param mixed      $uConfig    configuration
     * @param IInterface $uInterface interface class
     *
     * @return ServeTask
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
        $tPort = 1984;

        $this->interface->writeColor("yellow", sprintf("Built-in server started on port %d.", $tPort));
        $this->interface->writeColor("yellow", sprintf("Navigate to http://localhost:%d/\n", $tPort));
        $this->interface->write("Ctrl-C to stop.");
        passthru(
            "\"" . PHP_BINARY . "\" -S localhost:{$tPort} -t \"" . Core::$instance->loader->basepath . "\" index.php"
        );

        return 0;
    }
}
