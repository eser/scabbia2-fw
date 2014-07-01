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

namespace Scabbia\Commands;

use Scabbia\Framework\Core;
use Scabbia\Helpers\FileSystem;
use Scabbia\Helpers\Runtime;
use Scabbia\Interfaces\Console;
use Scabbia\Yaml\Parser;
use \RuntimeException;

/**
 * Commands functionality for framework
 *
 * @package     Scabbia\Commands
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 */
class Commands
{
    /** @type object $commands the commands read from commands file */
    public static $commands = [];


    /**
     * Loads the commands file.
     *
     * @param string $uCommandsConfigPath The path of commands configuration file
     *
     * @return void
     */
    public static function load($uCommandsConfigPath)
    {
        // load commands.yml
        $tCommandsYamlPath = FileSystem::combinePaths(Core::$basepath, Core::translateVariables($uCommandsConfigPath));
        $tCommandsConfig = Core::cachedRead(
            $tCommandsYamlPath,
            function () use ($tCommandsYamlPath) {
                $tParser = new Parser();
                return $tParser->parse(FileSystem::read($tCommandsYamlPath));
            },
            [
                "ttl" => 60 * 60
            ]
        );

        // register psr-0 source paths to composer.
        $tPaths = [];
        foreach ($tCommandsConfig["sources"] as $tPath) {
            $tPaths[] = Core::translateVariables($tPath);
        }

        Core::$composerAutoloader->set(false, $tPaths);

        // register commands
        foreach ($tCommandsConfig["commands"] as $tCommandKey => $tCommand) {
            self::$commands[$tCommandKey] = $tCommand;
        }
    }

    /**
     * Executes given command.
     *
     * @param array $uCommands The set of command line arguments
     *
     * @throws RuntimeException if command is not found
     * @return int exit code
     */
    public static function execute(array $uCommands)
    {
        $tCommandName = trim(array_shift($uCommands));

        if (isset(self::$commands[$tCommandName])) {
            $tCommand = self::$commands[$tCommandName];

            if (isset($tCommand["class"])) {
                $tClass = $tCommand["class"];
                $tCallbacks = [];
            } else {
                $tClass = null;
                $tCallbacks = (array)$tCommand["callback"];
            }

            if (isset($tCommand["config"])) {
                $tConfig = $tCommand["config"];
            } else {
                $tConfig = null;
            }
        } elseif (class_exists($tCommandName, true)) {
            $tClass = $tCommandName;
            $tCallbacks = [];
            $tConfig = null;
        } else {
            throw new RuntimeException("Command not found - " . $tCommandName . ".");
        }

        $tOutput = new Console();

        if ($tClass !== null) {
            $tInstance = new $tClass ($tConfig, $tOutput);
            return $tInstance->executeCommand($uCommands);
        } else {
            foreach ($tCallbacks as $tCallback) {
                $tReturn = call_user_func_array(
                    Runtime::callbacks($tCallback),
                    $uCommands
                );

                if ($tReturn !== null && $tReturn !== 0) {
                    return $tReturn;
                }
            }
        }

        return 0;
    }
}
