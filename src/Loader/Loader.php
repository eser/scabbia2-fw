<?php
/**
 * Scabbia2 PHP Framework Code
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        http://github.com/scabbiafw/scabbia2-fw for the canonical source repository
 * @copyright   2010-2014 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Loader;

use Scabbia\Framework\Core;
use Scabbia\Tasks\Tasks;
use InvalidArgumentException;

/**
 * Loader implements a PSR-4 and PSR-0 class loader
 *
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
 *
 * This class is loosely based on the Symfony UniversalClassLoader.
 *
 * @package     Scabbia\Loader
 * @author      Fabien Potencier <fabien@symfony.com>
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class Loader
{
    const LEVELS = 2;

    // PSR-4
    protected $prefixLengthsPsr4 = [];
    protected $prefixDirsPsr4 = [];
    protected $fallbackDirsPsr4 = [];

    // PSR-0
    protected $prefixesPsr0 = [];
    protected $fallbackDirsPsr0 = [];

    protected $classMap = [];
    protected $pushStack = [];

    /** @type string $basepath the base directory which framework runs in */
    public $basepath = null;
    /** @type string $vendorpath the vendor directory for 3rd party components */
    public $vendorpath = null;


    /**
     * Loads the framework
     *
     * @param string $uParameters the settings for loading procecedure
     *
     * @return Loader an instantiated Loader object
     */
    public static function load($uParameters = [])
    {
        $tBasePath = isset($uParameters["basepath"]) ? $uParameters["basepath"] : null;

        $tInstance = new static($tBasePath);
        if ($tInstance->vendorpath !== null) {
            $tInstance->importFromComposer();
        }
        $tInstance->register(true);

        // MD - initializes the autoloader and framework variables.
        Core::init($tInstance);

        if (isset($uParameters["projects"])) {
            // MD - read the application definitions from project.yml file and cache
            // MD - its content into cache/project.yml.php
            foreach ((array)$uParameters["projects"] as $tProjectFile) {
                Core::loadProject($tProjectFile);
            }

            // MD - pick which application is going to run
            Core::pickApplication();
        }

        if (isset($uParameters["tasks"])) {
            // MD - read the application definitions from tasks.yml file and cache
            // MD - its content into cache/tasks.yml.php
            foreach ((array)$uParameters["tasks"] as $tTasksFile) {
                Tasks::load($tTasksFile);
            }
        }

        if (isset($uParameters["run"]) && $uParameters["run"] === true) {
            Core::runApplication();
        }

        return $tInstance;
    }

    /**
     * Initializes a loader
     *
     * @param string|null $uBasePath the path of project files installed in
     *
     * @return Loader
     */
    public function __construct($uBasePath = null)
    {
        if ($uBasePath !== null) {
            $this->basepath = $uBasePath;
            $this->vendorpath = "{$uBasePath}/vendor";
        }

        for ($tLevel = self::LEVELS; $tLevel > 0; $tLevel--) {
                $this->prefixLengthsPsr4[] = [];
                $this->prefixDirsPsr4[] = [];
                $this->fallbackDirsPsr4[] = [];
                $this->prefixesPsr0[] = [];
                $this->fallbackDirsPsr0[] = [];
        }
    }

    /**
     * Registers loader with SPL autoloader stack
     *
     * @param bool $uPrepend whether to prepend the autoloader or not
     *
     * @return void
     */
    public function register($uPrepend = false)
    {
        spl_autoload_register([$this, "loadClass"], true, $uPrepend);
    }

    /**
     * Unregisters loader with SPL autoloader stack
     *
     * @return void
     */
    public function unregister()
    {
        spl_autoload_unregister([$this, "loadClass"]);
    }

    /**
     * Gets prefixes for PSR-0
     *
     * @param int $uLevel priority layer
     *
     * @return array
     */
    public function getPrefixesPsr0($uLevel = 1)
    {
        if (count($this->prefixesPsr0[$uLevel]) < 2) {
            return $this->prefixesPsr0[$uLevel];
        }

        return array_merge(...$this->prefixesPsr0[$uLevel]);
    }

    /**
     * Gets prefixes for PSR-4
     *
     * @param int $uLevel priority layer
     *
     * @return array
     */
    public function getPrefixesPsr4($uLevel = 1)
    {
        return $this->prefixDirsPsr4[$uLevel];
    }

    /**
     * Gets fallback directories for PSR-0
     *
     * @param int $uLevel priority layer
     *
     * @return array
     */
    public function getFallbackDirsPsr0($uLevel = 1)
    {
        return $this->fallbackDirsPsr0[$uLevel];
    }

    /**
     * Gets fallback directories for PSR-4
     *
     * @param int $uLevel priority layer
     *
     * @return array
     */
    public function getFallbackDirsPsr4($uLevel = 1)
    {
        return $this->fallbackDirsPsr4[$uLevel];
    }

    /**
     * Gets class map
     *
     * @return array
     */
    public function getClassMap()
    {
        return $this->classMap;
    }

    /**
     * Imports all settings from composer directory
     *
     * @return void
     */
    public function importFromComposer()
    {
        $tComposerPath = $this->vendorpath . "/composer";

        $tMap = require "{$tComposerPath}/autoload_namespaces.php";
        foreach ($tMap as $tNamespace => $tPath) {
            $this->setPsr0($tNamespace, $tPath);
        }

        $tMap = require "{$tComposerPath}/autoload_psr4.php";
        foreach ($tMap as $tNamespace => $tPath) {
            $this->setPsr4($tNamespace, $tPath);
        }

        $tClassMap = require "{$tComposerPath}/autoload_classmap.php";
        $this->addClassMap($tClassMap);
    }

    /**
     * @param array $uClassMap class to filename map
     *
     * @return void
     */
    public function addClassMap(array $uClassMap)
    {
        $this->classMap = array_merge($this->classMap, $uClassMap);
    }

    /**
     * @param array $uAliasMap alias to original class map
     *
     * @return void
     */
    public function addAlias(array $uAliasMap)
    {
        foreach ($uAliasMap as $uKey => $uValue) {
            class_alias($uValue, $uKey, true);
        }
    }

    /**
     * Registers a set of PSR-0 directories for a given prefix, either
     * appending or prepending to the ones previously set for this prefix
     *
     * @param string       $uPrefix  the prefix
     * @param array|string $uPaths   the PSR-0 root directories
     * @param int          $uLevel   priority layer
     *
     * @return void
     */
    public function addPsr0($uPrefix, $uPaths, $uLevel = 1)
    {
        if (!$uPrefix) {
            $this->fallbackDirsPsr0[$uLevel] = array_merge($this->fallbackDirsPsr0[$uLevel], (array)$uPaths);
            return;
        }

        $tFirst = $uPrefix[0];
        if (!isset($this->prefixesPsr0[$uLevel][$tFirst][$uPrefix])) {
            $this->prefixesPsr0[$uLevel][$tFirst][$uPrefix] = (array)$uPaths;
        } else {
            $this->prefixesPsr0[$uLevel][$tFirst][$uPrefix] = array_merge(
                $this->prefixesPsr0[$uLevel][$tFirst][$uPrefix],
                (array)$uPaths
            );
        }
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace, either
     * appending or prepending to the ones previously set for this namespace
     *
     * @param string        $uPrefix  the prefix/namespace, with trailing '\\'
     * @param array|string  $uPaths   the PSR-0 base directories
     * @param int           $uLevel   priority layer
     *
     * @throws InvalidArgumentException
     * @return void
     */
    public function addPsr4($uPrefix, $uPaths, $uLevel = 1)
    {
        if (!$uPrefix) {
            $this->fallbackDirsPsr4[$uLevel] = array_merge($this->fallbackDirsPsr4[$uLevel], (array)$uPaths);
        } elseif (!isset($this->prefixDirsPsr4[$uLevel][$uPrefix])) {
            // Register directories for a new namespace.
            $tLength = strlen($uPrefix);

            if ($uPrefix[$tLength - 1] !== "\\") {
                throw new InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }

            $this->prefixLengthsPsr4[$uLevel][$uPrefix[0]][$uPrefix] = $tLength;
            $this->prefixDirsPsr4[$uLevel][$uPrefix] = (array)$uPaths;
        } else {
            // Append directories for an already registered namespace.
            $this->prefixDirsPsr4[$uLevel][$uPrefix] = array_merge(
                $this->prefixDirsPsr4[$uLevel][$uPrefix],
                (array)$uPaths
            );
        }
    }

    /**
     * Registers a set of PSR-0 directories for a given prefix,
     * replacing any others previously set for this prefix
     *
     * @param string       $uPrefix  the prefix
     * @param array|string $uPaths   the PSR-0 base directories
     * @param int          $uLevel   priority layer
     *
     * @return void
     */
    public function setPsr0($uPrefix, $uPaths, $uLevel = 1)
    {
        if (!$uPrefix) {
            $this->fallbackDirsPsr0[$uLevel] = (array)$uPaths;
        } else {
            $this->prefixesPsr0[$uLevel][$uPrefix[0]][$uPrefix] = (array)$uPaths;
        }
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace,
     * replacing any others previously set for this namespace
     *
     * @param string       $uPrefix  the prefix/namespace, with trailing '\\'
     * @param array|string $uPaths   the PSR-4 base directories
     * @param int          $uLevel   priority layer
     *
     * @throws InvalidArgumentException
     * @return void
     */
    public function setPsr4($uPrefix, $uPaths, $uLevel = 1)
    {
        if (!$uPrefix) {
            $this->fallbackDirsPsr4[$uLevel] = (array)$uPaths;
        } else {
            $tLength = strlen($uPrefix);

            if ($uPrefix[$tLength - 1] !== "\\") {
                throw new InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }

            $this->prefixLengthsPsr4[$uLevel][$uPrefix[0]][$uPrefix] = $tLength;
            $this->prefixDirsPsr4[$uLevel][$uPrefix] = (array)$uPaths;
        }
    }

    /**
     * Loads the given class or interface
     *
     * @param  string    $uClass The name of the class
     *
     * @return bool true if loaded, false otherwise
     */
    public function loadClass($uClass)
    {
        if ($tFile = $this->findFile($uClass)) {
            loaderIncludeFile($tFile);

            return true;
        }

        return false;
    }

    /**
     * Finds the path to the file where the class is defined
     *
     * @param string       $uClass      the name of the class
     * @param array|string $uExtensions file extensions
     *
     * @return string|false the path if found, false otherwise
     */
    public function findFile($uClass, $uExtensions = [".php", ".hh"])
    {
        // work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
        if ($uClass[0] === "\\") {
            $uClass = substr($uClass, 1);
        }

        // class map lookup
        if (isset($this->classMap[$uClass])) {
            return $this->classMap[$uClass];
        }

        foreach ((array)$uExtensions as $tExtension) {
            if (($tFile = $this->findFileWithExtension($uClass, $tExtension)) !== null) {
                return $tFile;
            }
        }

        // Remember that this class does not exist.
        $this->classMap[$uClass] = false;
        return false;
    }

    /**
     * Finds the path to the file where the class is defined
     *
     * @param string  $uClass     the name of the class
     * @param string  $uExtension file extension
     *
     * @return string|false the path if found, false otherwise
     */
    public function findFileWithExtension($uClass, $uExtension)
    {
        // PSR-4 logical name
        $tLogicalPathPsr4 = strtr($uClass, "\\", "/") . $uExtension;
        $tFirst = $uClass[0];

        // PSR-0 logical name
        if (($tPos = strrpos($uClass, "\\")) !== false) {
            // namespaced class name
            $tLogicalPathPsr0 = substr($tLogicalPathPsr4, 0, $tPos + 1)
                . strtr(substr($tLogicalPathPsr4, $tPos + 1), "_", "/");
        } else {
            // PEAR-like class name
            $tLogicalPathPsr0 = strtr($uClass, "_", "/") . $uExtension;
        }

        for ($tLevel = 0; $tLevel < self::LEVELS; $tLevel++) {
            // PSR-4 lookup
            if (isset($this->prefixLengthsPsr4[$tLevel][$tFirst])) {
                foreach ($this->prefixLengthsPsr4[$tLevel][$tFirst] as $prefix => $tLength) {
                    if (strpos($uClass, $prefix) === 0) {
                        foreach ($this->prefixDirsPsr4[$tLevel][$prefix] as $tDirectory) {
                            if (file_exists($tFile = "{$tDirectory}/" . substr($tLogicalPathPsr4, $tLength))) {
                                return $tFile;
                            }
                        }
                    }
                }
            }

            // PSR-4 fallback dirs
            foreach ($this->fallbackDirsPsr4[$tLevel] as $tDirectory) {
                if (file_exists($tFile = "{$tDirectory}/{$tLogicalPathPsr4}")) {
                    return $tFile;
                }
            }

            // PSR-0 lookup
            if (isset($this->prefixesPsr0[$tLevel][$tFirst])) {
                foreach ($this->prefixesPsr0[$tLevel][$tFirst] as $prefix => $tDirectories) {
                    if (strpos($uClass, $prefix) === 0) {
                        foreach ($tDirectories as $tDirectory) {
                            if (file_exists($tFile = "{$tDirectory}/{$tLogicalPathPsr0}")) {
                                return $tFile;
                            }
                        }
                    }
                }
            }

            // PSR-0 fallback dirs
            foreach ($this->fallbackDirsPsr0[$tLevel] as $tDirectory) {
                if (file_exists($tFile = "{$tDirectory}/{$tLogicalPathPsr0}")) {
                    return $tFile;
                }
            }
        }
    }

    /**
     * Pushes current paths into stack
     *
     * @return void
     */
    public function push()
    {
        $this->pushStack[] = [
            "prefixLengthsPsr4" => $this->prefixLengthsPsr4,
            "prefixDirsPsr4" => $this->prefixDirsPsr4,
            "fallbackDirsPsr4" => $this->fallbackDirsPsr4,

            "prefixesPsr0" => $this->prefixesPsr0,
            "fallbackDirsPsr0" => $this->fallbackDirsPsr0,

            "classMap" => $this->classMap
        ];
    }

    /**
     * Pops previous paths from stack
     *
     * @return void
     */
    public function pop()
    {
        $tPopped = array_pop($this->pushStack);
        // TODO throw exception if $tPopped === false

        $this->prefixLengthsPsr4 = $tPopped["prefixLengthsPsr4"];
        $this->prefixDirsPsr4 = $tPopped["prefixDirsPsr4"];
        $this->fallbackDirsPsr4 = $tPopped["fallbackDirsPsr4"];

        $this->prefixesPsr0 = $tPopped["prefixesPsr0"];
        $this->fallbackDirsPsr0 = $tPopped["fallbackDirsPsr0"];

        $this->classMap = $tPopped["classMap"];
    }

    /**
     * Gets the composer folders
     *
     * @return Iterator composer folders in [prefix, folder, standard]
     */
    public function getComposerFolders()
    {
        for ($tLevel = 0; $tLevel < self::LEVELS; $tLevel++) {
            // PSR-4 lookup
            foreach ($this->prefixDirsPsr4[$tLevel] as $tPrefix => $tDirs) {
                foreach ($tDirs as $tDir) {
                    yield $tFolders[] = [$tPrefix, $tDir, "PSR-4"];
                }
            }

            // PSR-4 fallback dirs
            foreach ($this->fallbackDirsPsr4[$tLevel] as $tDir) {
                yield ["", $tDir, "PSR-4"];
            }

            // PSR-0 lookup
            foreach ($this->prefixesPsr0[$tLevel] as $tPrefixes) {
                foreach ($tPrefixes as $tPrefix => $tDirs) {
                    foreach ($tDirs as $tDir) {
                        yield [$tPrefix, $tDir, "PSR-0"];
                    }
                }
            }

            // PSR-0 fallback dirs
            foreach ($this->fallbackDirsPsr0[$tLevel] as $tDir) {
                yield ["", $tDir, "PSR-0"];
            }
        }
    }
}

/**
 * Scope isolated include
 *
 * Prevents access to $this/self from included files
 */
function loaderIncludeFile($uFile)
{
    include $uFile;
}
