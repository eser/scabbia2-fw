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

use Scabbia\Framework\Io;
use Scabbia\Yaml\Parser;

/**
 * Core framework functionality.
 *
 * @package     Scabbia\Framework
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 */
class Core
{
    /**
     * @type string $basepath the base directory which framework runs in
     */
    public static $basepath = null;

    /**
     * @type array $variable array of framework variables
     */
    public static $variables = [];

    /**
     * @type object $composerAutoloader the instance of the composer's autoloader class
     */
    public static $composerAutoloader = null;


    /**
     * Initializes the framework to be ready to boot.
     *
     * @param object $uComposerAutoloader The instance of the composer's autoloader class
     */
    public static function init($uComposerAutoloader)
    {
        mb_internal_encoding("UTF-8");

        self::$composerAutoloader = $uComposerAutoloader;
        self::setVariables();
    }

    /**
     * Loads the project.
     *
     * @param string $uProjectConfigPath The path of project configuration file
     */
    public static function loadProject($uProjectConfigPath)
    {
        // TODO load project.yml
        $tProjectYaml = file_get_contents(Io::combinePaths(self::$basepath, $uProjectConfigPath));
        $tParser = new Parser();
        $tProjectConfig = $tParser->parse($tProjectYaml);

        // TODO test cases for applications, and bind configuration to app
        foreach ($tProjectConfig as $tApplicationKey => $tApplicationConfig) {
            $tTargetApplication = $tApplicationKey;

            if (isset($tApplicationConfig["tests"])) {
                foreach ($tApplicationConfig["tests"] as $tApplicationTest) {
                    $tSubject = self::translateVariables($tApplicationTest[0]);
                    if (!preg_match($tApplicationTest[1], $tSubject)) {
                        $tTargetApplication = false;
                        break;
                    }
                }
            }

            if ($tTargetApplication !== false) {
                break;
            }
        }

        // Application::$config = $tProjectConfig[$tTargetApplication];

        // TODO initialize the proper environment and bind to core
        // TODO initialize application and bind to core
        // TODO load modules
    }

    /**
     * Replaces placeholders in given string with framework-variables.
     *
     * @param string $uInput the string with placeholders
     *
     * @return string translated string
     */
    public static function translateVariables($uInput)
    {
        foreach (self::$variables as $tKey => $tValue) {
            if (!is_scalar($tValue)) {
                continue;
            }

            $uInput = str_replace('{' . $tKey . '}', $tValue, $uInput);
        }

        return $uInput;
    }

    /**
     * Sets the variables.
     */
    protected static function setVariables()
    {
        if (self::$basepath === null) {
            $tScriptDirectory = pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_DIRNAME);

            if ($tScriptDirectory !== ".") {
                self::$basepath = Io::combinePaths(getcwd(), $tScriptDirectory);
            } else {
                self::$basepath = getcwd();
            }
        }

        // secure
        if (isset($_SERVER["HTTPS"]) &&
            ((string)$_SERVER["HTTPS"] === "1" || strcasecmp($_SERVER["HTTPS"], "on") === 0)) {
            self::$variables["secure"] = true;
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https") {
            self::$variables["secure"] = true;
        } else {
            self::$variables["secure"] = false;
        }

        // protocol
        if (PHP_SAPI === "cli") {
            self::$variables["protocol"] = "CLI";
        } elseif (isset($_SERVER["SERVER_PROTOCOL"]) && $_SERVER["SERVER_PROTOCOL"] === "HTTP/1.0") {
            self::$variables["protocol"] = "HTTP/1.0";
        } else {
            self::$variables["protocol"] = "HTTP/1.1";
        }

        // host
        if (isset($_SERVER["HTTP_HOST"]) && strlen($_SERVER["HTTP_HOST"]) > 0) {
            self::$variables["host"] = $_SERVER["HTTP_HOST"];
        } else {
            if (isset($_SERVER["SERVER_NAME"])) {
                self::$variables["host"] = $_SERVER["SERVER_NAME"];
            } elseif (isset($_SERVER["SERVER_ADDR"])) {
                self::$variables["host"] = $_SERVER["SERVER_ADDR"];
            } elseif (isset($_SERVER["LOCAL_ADDR"])) {
                self::$variables["host"] = $_SERVER["LOCAL_ADDR"];
            } else {
                self::$variables["host"] = "localhost";
            }

            if (isset($_SERVER["SERVER_PORT"])) {
                if (self::$https) {
                    if ($_SERVER["SERVER_PORT"] !== "443") {
                        self::$variables["host"] .= $_SERVER["SERVER_PORT"];
                    }
                } else {
                    if ($_SERVER["SERVER_PORT"] !== "80") {
                        self::$variables["host"] .= $_SERVER["SERVER_PORT"];
                    }
                }
            }
        }

        // os
        if (strncasecmp(PHP_OS, "WIN", 3) === 0) {
            self::$variables["os"] = "windows";
        } else {
            self::$variables["os"] = "*nix";
        }
    }
}
