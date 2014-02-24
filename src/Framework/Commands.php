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

namespace Scabbia\Framework;

use Scabbia\Framework\Core;
use Scabbia\Framework\Io;
use Scabbia\Output\ConsoleOutput;
use Scabbia\Yaml\Parser;

/**
 * Commands functionality for framework
 *
 * @package     Scabbia\Framework
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 */
class Commands
{
    /**
     * @type object $commands the commands read from commands file
     */
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
        $tCommandsYamlPath = Io::combinePaths(Core::$basepath, $uCommandsConfigPath);
        $tCommandsYamlCachePath = Core::$basepath . "/writable/cache/" . crc32($tCommandsYamlPath);

        $tCommandsConfig = Io::readFromCache(
            $tCommandsYamlCachePath,
            function () use ($tCommandsYamlPath) {
                $tParser = new Parser();
                return $tParser->parse(Io::read($tCommandsYamlPath));
            },
            60 * 60
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
     * @throws \RuntimeException if command is not found
     * @return int exit code
     */
    public static function execute(array $uCommands)
    {
        $tCommand = trim(array_shift($uCommands));

        if (isset(self::$commands[$tCommand])) {
            $tCallbacks = (array)self::$commands[$tCommand]["callback"];

            if (isset(self::$commands[$tCommand]["config"])) {
                $tConfig = self::$commands[$tCommand]["config"];
            } else {
                $tConfig = null;
            }
        } elseif (is_callable($tCommand)) {
            $tCallbacks = [$tCommand];
            $tConfig = null;
        }

        if (!isset($tCallbacks)) {
            throw new \RuntimeException("Command not found - " . $tCommand . ".");
        }

        $tOutput = new ConsoleOutput();

        foreach ($tCallbacks as $tCallback) {
            call_user_func($tCallback, $uCommands, $tConfig, $tOutput);
        }

        return 0;
    }
}
