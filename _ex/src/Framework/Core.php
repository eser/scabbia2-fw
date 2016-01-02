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

namespace Scabbia\Framework;

use Scabbia\Framework\ApplicationBase;
use Scabbia\Generators\GeneratorRegistry;
use Scabbia\Helpers\FileSystem;
use Scabbia\Config\Config;
use Scabbia\Loader\Loader;

/**
 * Core framework functionality
 *
 * @package     Scabbia\Framework
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       1.0.0
 */
// MD-TITLE Core Class
class Core
{
    /** @type Core $instance the singleton instance of core */
    public static $instance = null;
    /** @type Loader $loader the instance of the autoloader class */
    public $loader;
    /** @type array $variable array of framework variables */
    public $variables;
    /** @type array $variablesPlaceholderCache cache for framework variables which will be used for translation */
    public $variablesPlaceholderCache = [[], []];
    /** @type Config project configuration */
    public $projectConfiguration;
    /** @type array $runningApplications array of running applications */
    public $runningApplications = [];


    /**
     * Initializes a new instance of Core class
     *
     * @param Loader $uLoader The instance of the autoloader class
     *
     * @return Core
     */
    public function __construct(Loader $uLoader)
    {
        if (static::$instance === null) {
            static::$instance = $this;
        }

        // MD assign autoloader to Core::$loader
        $this->loader = $uLoader;

        // MD determine environment variables
        // basepath
        $this->variables = [
            "basepath" => &$this->loader->basepath
        ];

        // secure
        if (isset($_SERVER["HTTPS"]) &&
            ((string)$_SERVER["HTTPS"] === "1" || strcasecmp($_SERVER["HTTPS"], "on") === 0)) {
            $this->variables["secure"] = true;
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https") {
            $this->variables["secure"] = true;
        } else {
            $this->variables["secure"] = false;
        }

        // protocol
        if (PHP_SAPI === "cli") {
            $this->variables["protocol"] = "CLI";
        } elseif (isset($_SERVER["SERVER_PROTOCOL"]) && $_SERVER["SERVER_PROTOCOL"] === "HTTP/1.0") {
            $this->variables["protocol"] = "HTTP/1.0";
        } else {
            $this->variables["protocol"] = "HTTP/1.1";
        }

        // host
        if (isset($_SERVER["HTTP_HOST"]) && strlen($_SERVER["HTTP_HOST"]) > 0) {
            $this->variables["host"] = $_SERVER["HTTP_HOST"];
        } else {
            if (isset($_SERVER["SERVER_NAME"])) {
                $this->variables["host"] = $_SERVER["SERVER_NAME"];
            } elseif (isset($_SERVER["SERVER_ADDR"])) {
                $this->variables["host"] = $_SERVER["SERVER_ADDR"];
            } elseif (isset($_SERVER["LOCAL_ADDR"])) {
                $this->variables["host"] = $_SERVER["LOCAL_ADDR"];
            } else {
                $this->variables["host"] = "localhost";
            }

            if (isset($_SERVER["SERVER_PORT"])) {
                if ($this->https) {
                    if ($_SERVER["SERVER_PORT"] !== "443") {
                        $this->variables["host"] .= $_SERVER["SERVER_PORT"];
                    }
                } else {
                    if ($_SERVER["SERVER_PORT"] !== "80") {
                        $this->variables["host"] .= $_SERVER["SERVER_PORT"];
                    }
                }
            }
        }

        // os
        if (strncasecmp(PHP_OS, "WIN", 3) === 0) {
            $this->variables["os"] = "windows";
        } else {
            $this->variables["os"] = "*nix";
        }

        $this->updateVariablesCache();

        $this->projectConfiguration = new Config();
    }

    // MD ## Core::loadProject method
    /**
     * Loads the project file
     *
     * @param string $uProjectConfigPath The path of project configuration file
     *
     * @return void
     */
    public function loadProject($uProjectConfigPath)
    {
        // MD add configuration file to project configuration stack
        $this->projectConfiguration->add(
            FileSystem::combinePaths($this->loader->basepath, $this->translateVariables($uProjectConfigPath))
        );
    }

    // MD ## Core::pickApplication method
    /**
     * Picks the best suited application
     *
     * @return void
     */
    public function pickApplication()
    {
        // MD loop through application definitions in project configuration
        // test cases for applications, and bind configuration to app
        foreach ($this->projectConfiguration->get() as $tApplicationKey => $tApplicationConfig) {
            // TODO is sanitizing $tApplicationKey needed for paths?
            $tTargetApplication = $tApplicationKey;

            // MD - test conditions for each application definition
            if (isset($tApplicationConfig["tests"])) {
                foreach ($tApplicationConfig["tests"] as $tApplicationTest) {
                    $tSubject = $this->translateVariables($tApplicationTest[0]);
                    if (!preg_match($tApplicationTest[1], $tSubject)) {
                        $tTargetApplication = false;
                        break;
                    }
                }
            }

            // MD - if selected application fits all test conditions, run it
            if ($tTargetApplication !== false) {
                $tApplicationWritablePath = $this->loader->basepath . "/var/generated/app.{$tTargetApplication}";
                $this->pushApplication($tApplicationConfig, $tApplicationWritablePath);
            }
        }
    }

    // MD ## Core::runApplication method
    /**
     * Runs an application
     *
     * @return void
     */
    public function runApplication()
    {
        foreach ($this->runningApplications as $tApplicationInfo) {
            if ($tApplicationInfo[0] !== null) {
                $tApplicationInfo[0]->generateRequestFromGlobals();
            }
        }

        if (ApplicationBase::$current !== null) {
            ApplicationBase::$current->generateRequestFromGlobals();
        }
    }

    // MD ## Core::pushApplication method
    /**
     * Push an application
     *
     * @param mixed  $uApplicationConfig the application configuration
     * @param string $uWritablePath      writable output folder
     *
     * @return void
     */
    public function pushApplication($uApplicationConfig, $uWritablePath)
    {
        // MD create application directory if it does not exist
        if (!file_exists($uWritablePath)) {
            mkdir($uWritablePath, 0777, true);
        }

        // MD push framework variables to undo application's own variable definitions
        $this->pushSourcePaths($uApplicationConfig);

        // MD include compilation file for the application
        // FIXME is it needed to be loaded before Core and ApplicationBase?
        if (file_exists($tFile = "{$uWritablePath}/compiled.php")) {
            require $tFile;
        } else {
            $tGeneratorRegistry = new GeneratorRegistry($uApplicationConfig, $uWritablePath);
            $tGeneratorRegistry->execute();
        }

        // MD add configuration entries too
        $uApplicationConfig += require "{$uWritablePath}/unified-config.php";

        // MD construct the application class
        $this->runningApplications[] = [ApplicationBase::$current, $this->variables];

        $tApplicationType = $uApplicationConfig["type"];
        $tApplication = new $tApplicationType ($uApplicationConfig, $uWritablePath);

        ApplicationBase::$current = $tApplication;
    }

    // MD ## Core::popApplication method
    /**
     * Pops an application
     *
     * @return void
     */
    public function popApplication()
    {
        // MD pop framework variables
        list(ApplicationBase::$current, $this->variables) = array_pop($this->runningApplications);
        $this->popSourcePaths();
    }

    /**
     * Pushes source paths into loader's stack
     *
     * @param mixed  $uConfig the configuration
     *
     * @return void
     */
    public function pushSourcePaths($uConfig)
    {
        if (!isset($uConfig["autoload"])) {
            return;
        }

        if (isset($uConfig["codepools"])) {
            $tCodepools = (array)$uConfig["codepools"];
        } else {
            $tCodepools = ["local", "core"];
        }

        $this->loader->push();

        $tPreviousPrependedPaths = $this->loader->getPrefixesPsr4(0);
        $tPreviousPrependedPaths[false] = $this->loader->getFallbackDirsPsr4(0);

        foreach ($uConfig["autoload"] as $tNamespace => $tPaths) {
            $tLoaderNamespace = ($tNamespace !== "default") ? $tNamespace : false;

            $tTranslatedPaths = [];
            foreach ((array)$tPaths as $tPath) {
                foreach ($tCodepools as $tCodepool) {
                    $tTranslatedPath = str_replace(
                        "{codepool}",
                        $tCodepool,
                        $this->translateVariables($tPath)
                    );

                    if (isset($tPreviousPrependedPaths[$tLoaderNamespace]) &&
                        !in_array($tTranslatedPath, $tPreviousPrependedPaths[$tLoaderNamespace])) {
                        $tTranslatedPaths[] = $tTranslatedPath;
                    }
                }
            }

            $this->loader->addPsr4($tLoaderNamespace, $tTranslatedPaths, 0);
        }
    }

    /**
     * Pops source paths in loader's stack
     *
     * @return void
     */
    public function popSourcePaths()
    {
        $this->loader->pop();
    }

    /**
     * Updates the cache of variables which will be used in translateVariables method
     *
     * @return void
     */
    public function updateVariablesCache()
    {
        $this->variablesPlaceholderCache[0] = [];
        $this->variablesPlaceholderCache[1] = [];

        foreach ($this->variables as $tKey => $tValue) {
            if (!is_scalar($tValue)) {
                continue;
            }

            $this->variablesPlaceholderCache[0][] = "{" . $tKey . "}";
            $this->variablesPlaceholderCache[1][] = $tValue;
        }
    }

    /**
     * Replaces placeholders in given string with framework-variables
     *
     * @param string $uInput the string with placeholders
     *
     * @return string translated string
     */
    public function translateVariables($uInput)
    {
        return str_replace($this->variablesPlaceholderCache[0], $this->variablesPlaceholderCache[1], $uInput);
    }

    /**
     * Reads the contents from cache folder as long as it is not expired
     * If the file is expired, invokes callback method and caches output
     *
     * @param string      $uKey          key for the value going to be cached
     * @param mixed       $uDefaultValue the default value
     * @param array       $uOptions      options
     *
     * @return mixed the result
     */
    public function cachedRead($uKey, $uDefaultValue, array $uOptions = [])
    {
        $tCacheFile = $this->loader->basepath . "/var/cache/" . crc32($uKey);

        return FileSystem::readFromCacheFile(
            $tCacheFile,
            $uDefaultValue,
            $uOptions
        );
    }
}
