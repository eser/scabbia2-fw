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

use Scabbia\Framework\ApplicationBase;
use Scabbia\Helpers\FileSystem;
use Scabbia\Config\Config;

/**
 * Core framework functionality
 *
 * @package     Scabbia\Framework
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 */
class Core
{
    /** @type object $composerAutoloader the instance of the composer's autoloader class */
    public static $composerAutoloader = null;
    /** @type string $basepath the base directory which framework runs in */
    public static $basepath = null;
    /** @type array $variable array of framework variables */
    public static $variables = [];
    /** @type array $variablesPlaceholderCache cache for framework variables which will be used for translation */
    public static $variablesPlaceholderCache = [[], []];
    /** @type Config project configuration */
    public static $projectConfiguration = null;
    /** @type array $runningApplications array of running applications */
    public static $runningApplications = [];


    /**
     * Initializes the framework to be ready to boot
     *
     * @param object $uComposerAutoloader The instance of the composer's autoloader class
     *
     * @return void
     */
    public static function init($uComposerAutoloader)
    {
        mb_internal_encoding("UTF-8");

        self::$composerAutoloader = $uComposerAutoloader;

        if (self::$basepath === null) {
            $tScriptDirectory = pathinfo($_SERVER["SCRIPT_FILENAME"], PATHINFO_DIRNAME);

            if ($tScriptDirectory !== ".") {
                self::$basepath = FileSystem::combinePaths(getcwd(), $tScriptDirectory);
            } else {
                self::$basepath = getcwd();
            }

            self::$variables["basepath"] = &self::$basepath;
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

        self::updateVariablesCache();
    }

    /**
     * Loads the project file
     *
     * @param string $uProjectConfigPath The path of project configuration file
     *
     * @return void
     */
    public static function loadProject($uProjectConfigPath)
    {
        if (self::$projectConfiguration === null) {
            self::$projectConfiguration = new Config();
        }

        self::$projectConfiguration->add(
            FileSystem::combinePaths(Core::$basepath, self::translateVariables($uProjectConfigPath))
        );
    }

    /**
     * Picks the best suited application
     *
     * @return void
     */
    public static function pickApplication()
    {
        // test cases for applications, and bind configuration to app
        foreach (self::$projectConfiguration->get() as $tApplicationKey => $tApplicationConfig) {
            // TODO is sanitizing $tApplicationKey needed for paths?
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
                $tApplicationWritablePath = self::$basepath . "/writable/generated/app.{$tTargetApplication}";
                self::runApplication($tApplicationConfig, $tApplicationWritablePath);
            }
        }
    }

    /**
     * Runs an application
     *
     * @param mixed  $uApplicationConfig the application configuration
     * @param string $uWritablePath      writable output folder
     *
     * @return void
     */
    public static function runApplication($uApplicationConfig, $uWritablePath)
    {
        // run compiled package
        if (file_exists($tCompiledFile = "{$uWritablePath}/compiled.php")) {
            require $tCompiledFile;
        }

        // push framework variables
        $tPaths = self::pushComposerPaths($uApplicationConfig);
        self::$runningApplications[] = [ApplicationBase::$current, self::$variables];

        // construct the application class
        $tApplicationType = $uApplicationConfig["type"];
        $tApplication = new $tApplicationType ($uApplicationConfig, $tPaths, $uWritablePath);

        ApplicationBase::$current = $tApplication;
        $tApplication->generateRequestFromGlobals();

        // pop framework variables
        list(ApplicationBase::$current, self::$variables) = array_pop(self::$runningApplications);
        self::popComposerPaths();
    }

    /**
     * Pushes composer paths into stack
     *
     * @param mixed  $uApplicationConfig the application configuration
     *
     * @return array array of source paths
     */
    public static function pushComposerPaths($uApplicationConfig)
    {
        // register psr-0 source paths to composer.
        if (ApplicationBase::$current !== null) {
            $tPaths = ApplicationBase::$current->paths;
        } else {
            $tPaths = [];
        }

        foreach ($uApplicationConfig["sources"] as $tPath) {
            // FIXME in_array may be placed here to check for paths against duplication, but it's a rare case.
            $tPaths[] = self::translateVariables($tPath);
        }

        self::$composerAutoloader->set(false, $tPaths);

        return $tPaths;
    }

    /**
     * Pops composer paths in stack
     *
     * @return void
     */
    public static function popComposerPaths()
    {
        if (ApplicationBase::$current !== null) {
            self::$composerAutoloader->set(false, ApplicationBase::$current->paths);
        } else {
            self::$composerAutoloader->set(false, []);
        }
    }

    /**
     * Updates the cache of variables which will be used in translateVariables method
     *
     * @return void
     */
    public static function updateVariablesCache()
    {
        self::$variablesPlaceholderCache[0] = [];
        self::$variablesPlaceholderCache[1] = [];

        foreach (self::$variables as $tKey => $tValue) {
            if (!is_scalar($tValue)) {
                continue;
            }

            self::$variablesPlaceholderCache[0][] = "{" . $tKey . "}";
            self::$variablesPlaceholderCache[1][] = $tValue;
        }
    }

    /**
     * Replaces placeholders in given string with framework-variables
     *
     * @param string $uInput the string with placeholders
     *
     * @return string translated string
     */
    public static function translateVariables($uInput)
    {
        return str_replace(self::$variablesPlaceholderCache[0], self::$variablesPlaceholderCache[1], $uInput);
    }

    /**
     * Reads the contents from cache folder as long as it is not expired
     * If the file is expired, invokes callback method and caches output
     *
     * @param string      $uPath         the relative path
     * @param mixed       $uDefaultValue the default value
     * @param array       $uOptions      options
     *
     * @return mixed the result
     */
    public static function cachedRead($uPath, $uDefaultValue, array $uOptions = [])
    {
        $tCacheFile = self::$basepath . "/writable/cache/" . crc32(realpath($uPath));

        return FileSystem::readFromCacheFile(
            $tCacheFile,
            $uDefaultValue,
            $uOptions
        );
    }
}
