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
use Scabbia\Yaml\Parser;

/**
 * Commands functionality for framework.
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
    public static $commands = null;


    /**
     * Loads the commands file.
     *
     * @param string $uCommandsConfigPath The path of commands configuration file
     */
    public static function load($uCommandsConfigPath)
    {
        // load commands.yml
        $tCommandsYamlPath = Io::combinePaths(Core::$basepath, $uCommandsConfigPath);
        $tCommandsYamlCachePath = Core::$basepath . "/cache/" . crc32($tCommandsYamlPath);

        self::$commands = Io::readFromCache(
            $tCommandsYamlCachePath,
            function () use ($tCommandsYamlPath) {
                $tParser = new Parser();
                return $tParser->parse(Io::read($tCommandsYamlPath));
            },
            60 * 60
        );

        // register psr-0 source paths to composer.
        $tPaths = [];
        foreach (self::$commands["sources"] as $tPath) {
            $tPaths[] = Core::translateVariables($tPath);
        }

        Core::$composerAutoloader->set(false, $tPaths);
    }

    /**
     * Executes given command.
     *
     * @param array $uCommands The set of command line arguments.
     *
     * @throws \RuntimeException if command is not found
     */
    public static function execute(array $uCommands)
    {
        $tCommand = trim($uCommands[0]);

        if (isset(self::$commands["commands"][$tCommand])) {
            $tCallbacks = (array)self::$commands["commands"][$tCommand];
        } elseif (is_callable($tCommand)) {
            $tCallbacks = [$tCommand];
        }

        if (!isset($tCallbacks)) {
            throw new \RuntimeException("Command not found - " . $tCommand . ".");
        }

        foreach ($tCallbacks as $tCallback) {
            call_user_func($tCallback);
        }
    }
}
