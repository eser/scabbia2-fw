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

namespace Scabbia\Tasks;

use Scabbia\Framework\Core;
use Scabbia\Helpers\FileSystem;
use Scabbia\Helpers\Runtime;
use Scabbia\Interfaces\Console;
use Scabbia\Objects\CommandInterpreter;
use Scabbia\Yaml\Parser;
use RuntimeException;

/**
 * Tasks functionality for framework
 *
 * @package     Scabbia\Tasks
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 */
class Tasks
{
    /** @type object $tasks the tasks read from tasks file */
    public static $tasks = [];
    /** @type array $config tasks configuration */
    public static $config = null;


    /**
     * Constructor to prevent new instances of Tasks class
     *
     * @return Tasks
     */
    final private function __construct()
    {
    }

    /**
     * Clone method to prevent duplication of Tasks class
     *
     * @return Tasks
     */
    final private function __clone()
    {
    }

    /**
     * Unserialization method to prevent restoration of Tasks class
     *
     * @return Tasks
     */
    final private function __wakeup()
    {
    }

    /**
     * Loads the tasks file.
     *
     * @param string $uTasksConfigPath The path of tasks configuration file
     *
     * @return void
     */
    public static function load($uTasksConfigPath)
    {
        // load tasks.yml
        $tTasksYamlPath = FileSystem::combinePaths(Core::$basepath, Core::translateVariables($uTasksConfigPath));
        self::$config = Core::cachedRead(
            $tTasksYamlPath,
            function () use ($tTasksYamlPath) {
                $tParser = new Parser();
                return $tParser->parse(FileSystem::read($tTasksYamlPath));
            },
            [
                "ttl" => 60 * 60
            ]
        );

        // register tasks
        foreach (self::$config["tasks"] as $tTaskKey => $tTask) {
            self::$tasks[$tTaskKey] = $tTask;
        }
    }

    /**
     * Executes given task.
     *
     * @param array $uTasks The set of task line arguments
     *
     * @throws RuntimeException if task is not found
     * @return int exit code
     */
    public static function execute(array $uTasks)
    {
        // register source paths to loader.
        Core::pushSourcePaths(self::$config);

        // TODO use interpreter
        // $tCommandInterpreter = new CommandInterpreter("Scabbia", "Scabbia Command Line Tool");

        $tTaskName = trim(array_shift($uTasks));

        if (isset(self::$tasks[$tTaskName])) {
            $tTask = self::$tasks[$tTaskName];

            if (isset($tTask["class"])) {
                $tClass = $tTask["class"];
                $tCallbacks = [];
            } else {
                $tClass = null;
                $tCallbacks = (array)$tTask["callback"];
            }

            if (isset($tTask["config"])) {
                $tConfig = $tTask["config"];
            } else {
                $tConfig = null;
            }
        } elseif (class_exists($tTaskName, true)) {
            $tClass = $tTaskName;
            $tCallbacks = [];
            $tConfig = null;
        } else {
            throw new RuntimeException("Task not found - " . $tTaskName . ".");
        }

        $tOutput = new Console();

        if ($tClass !== null) {
            $tInstance = new $tClass ($tConfig, $tOutput);
            $tReturnCode = $tInstance->executeTask($uTasks);
        } else {
            $tReturnCode = 0;

            foreach ($tCallbacks as $tCallback) {
                $tReturn = call_user_func(
                    Runtime::callbacks($tCallback),
                    ...$uTasks
                );

                if ($tReturn !== null && $tReturn !== 0) {
                    $tReturnCode = $tReturn;
                    break;
                }
            }
        }

        Core::popSourcePaths();

        return $tReturnCode;
    }
}
