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

namespace Scabbia\Loaders;

use \InvalidArgumentException;

/**
 * Composer Autoloader Class
 *
 * @package     Scabbia\Loaders
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class Composer
{
    // PSR-4
    protected $prefixLengthsPsr4 = [];
    protected $prefixDirsPsr4 = [];
    protected $fallbackDirsPsr4 = [];

    // PSR-0
    protected $prefixesPsr0 = [];
    protected $fallbackDirsPsr0 = [];

    protected $classMap = [];


    /**
     * Initializes Composer autoloader and registers it
     *
     * @param string $uComposerPath the path of composer files installed in
     *
     * @return Composer the instance
     */
    public static function init($uComposerPath)
    {
        $tInstance = new static();

        $tMap = require "{$uComposerPath}/autoload_namespaces.php";
        foreach ($tMap as $tNamespace => $tPath) {
            $tInstance->set($tNamespace, $tPath);
        }

        $tMap = require "{$uComposerPath}/autoload_psr4.php";
        foreach ($tMap as $tNamespace => $tPath) {
            $tInstance->setPsr4($tNamespace, $tPath);
        }

        $tClassMap = require "{$uComposerPath}/autoload_classmap.php";
        if ($tClassMap) {
            $tInstance->addClassMap($tClassMap);
        }

        $tInstance->register(true);

        return $tInstance;
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
        spl_autoload_register([&$this, "loadClass"], true, $uPrepend);
    }

    /**
     * Unregisters loader with SPL autoloader stack
     *
     * @return void
     */
    public function unregister()
    {
        spl_autoload_unregister([&$this, "loadClass"]);
    }

    /**
     * Gets prefixes for PSR-0
     *
     * @return array
     */
    public function getPrefixes()
    {
        return call_user_func_array('array_merge', $this->prefixesPsr0);
    }

    /**
     * Gets prefixes for PSR-4
     * @return array
     */
    public function getPrefixesPsr4()
    {
        return $this->prefixDirsPsr4;
    }

    /**
     * Gets fallback directories for PSR-0
     *
     * @return array
     */
    public function getFallbackDirs()
    {
        return $this->fallbackDirsPsr0;
    }

    /**
     * Gets fallback directories for PSR-4
     *
     * @return array
     */
    public function getFallbackDirsPsr4()
    {
        return $this->fallbackDirsPsr4;
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
     * @param array $uClassMap class to filename map
     *
     * @return void
     */
    public function addClassMap(array $uClassMap)
    {
        if ($this->classMap) {
            $this->classMap = array_merge($this->classMap, $uClassMap);
        } else {
            $this->classMap = $uClassMap;
        }
    }

    /**
     * Registers a set of PSR-0 directories for a given prefix, either
     * appending or prepending to the ones previously set for this prefix
     *
     * @param string       $uPrefix  the prefix
     * @param array|string $uPaths   the PSR-0 root directories
     * @param bool         $uPrepend whether to prepend the directories
     *
     * @return void
     */
    public function add($uPrefix, $uPaths, $uPrepend = false)
    {
        if (!$uPrefix) {
            if ($uPrepend) {
                $this->fallbackDirsPsr0 = array_merge(
                    (array)$uPaths,
                    $this->fallbackDirsPsr0
                );
            } else {
                $this->fallbackDirsPsr0 = array_merge(
                    $this->fallbackDirsPsr0,
                    (array)$uPaths
                );
            }

            return;
        }

        $tFirst = $uPrefix[0];
        if (!isset($this->prefixesPsr0[$tFirst][$uPrefix])) {
            $this->prefixesPsr0[$tFirst][$uPrefix] = (array)$uPaths;

            return;
        }
        if ($uPrepend) {
            $this->prefixesPsr0[$tFirst][$uPrefix] = array_merge(
                (array)$uPaths,
                $this->prefixesPsr0[$tFirst][$uPrefix]
            );
        } else {
            $this->prefixesPsr0[$tFirst][$uPrefix] = array_merge(
                $this->prefixesPsr0[$tFirst][$uPrefix],
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
     * @param bool          $uPrepend whether to prepend the directories
     *
     * @throws InvalidArgumentException
     * @return void
     */
    public function addPsr4($uPrefix, $uPaths, $uPrepend = false)
    {
        if (!$uPrefix) {
            // Register directories for the root namespace.
            if ($uPrepend) {
                $this->fallbackDirsPsr4 = array_merge(
                    (array)$uPaths,
                    $this->fallbackDirsPsr4
                );
            } else {
                $this->fallbackDirsPsr4 = array_merge(
                    $this->fallbackDirsPsr4,
                    (array)$uPaths
                );
            }
        } elseif (!isset($this->prefixDirsPsr4[$uPrefix])) {
            // Register directories for a new namespace.
            $length = strlen($uPrefix);
            if ($uPrefix[$length - 1] !== "\\") {
                throw new InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }
            $this->prefixLengthsPsr4[$uPrefix[0]][$uPrefix] = $length;
            $this->prefixDirsPsr4[$uPrefix] = (array)$uPaths;
        } elseif ($uPrepend) {
            // Prepend directories for an already registered namespace.
            $this->prefixDirsPsr4[$uPrefix] = array_merge(
                (array)$uPaths,
                $this->prefixDirsPsr4[$uPrefix]
            );
        } else {
            // Append directories for an already registered namespace.
            $this->prefixDirsPsr4[$uPrefix] = array_merge(
                $this->prefixDirsPsr4[$uPrefix],
                (array)$uPaths
            );
        }
    }

    /**
     * Registers a set of PSR-0 directories for a given prefix,
     * replacing any others previously set for this prefix
     *
     * @param string       $uPrefix the prefix
     * @param array|string $uPaths  the PSR-0 base directories
     *
     * @return void
     */
    public function set($uPrefix, $uPaths)
    {
        if (!$uPrefix) {
            $this->fallbackDirsPsr0 = (array) $uPaths;
        } else {
            $this->prefixesPsr0[$uPrefix[0]][$uPrefix] = (array)$uPaths;
        }
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace,
     * replacing any others previously set for this namespace
     *
     * @param string       $uPrefix the prefix/namespace, with trailing '\\'
     * @param array|string $uPaths  the PSR-4 base directories
     *
     * @throws InvalidArgumentException
     * @return void
     */
    public function setPsr4($uPrefix, $uPaths)
    {
        if (!$uPrefix) {
            $this->fallbackDirsPsr4 = (array)$uPaths;
        } else {
            $tLength = strlen($uPrefix);
            if ($uPrefix[$tLength - 1] !== "\\") {
                throw new InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }
            $this->prefixLengthsPsr4[$uPrefix[0]][$uPrefix] = $tLength;
            $this->prefixDirsPsr4[$uPrefix] = (array)$uPaths;
        }
    }

    /**
     * Loads the given class or interface
     *
     * @param  string    $class The name of the class
     *
     * @return bool true if loaded, false otherwise
     */
    public function loadClass($class)
    {
        if ($tFile = $this->findFile($class)) {
            composerIncludeFile($tFile);

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
        // PSR-4 lookup
        $tLogicalPathPsr4 = strtr($uClass, "\\", "/") . $uExtension;

        $tFirst = $uClass[0];
        if (isset($this->prefixLengthsPsr4[$tFirst])) {
            foreach ($this->prefixLengthsPsr4[$tFirst] as $prefix => $length) {
                if (strpos($uClass, $prefix) === 0) {
                    foreach ($this->prefixDirsPsr4[$prefix] as $tDirectory) {
                        if (file_exists($tFile = "{$tDirectory}/" . substr($tLogicalPathPsr4, $length))) {
                            return $tFile;
                        }
                    }
                }
            }
        }

        // PSR-4 fallback dirs
        foreach ($this->fallbackDirsPsr4 as $tDirectory) {
            if (file_exists($tFile = "{$tDirectory}/{$tLogicalPathPsr4}")) {
                return $tFile;
            }
        }

        // PSR-0 lookup
        if (($tPos = strrpos($uClass, "\\")) !== false) {
            // namespaced class name
            $tLogicalPathPsr0 = substr($tLogicalPathPsr4, 0, $tPos + 1)
                . strtr(substr($tLogicalPathPsr4, $tPos + 1), "_", "/");
        } else {
            // PEAR-like class name
            $tLogicalPathPsr0 = strtr($uClass, "_", "/") . $uExtension;
        }

        if (isset($this->prefixesPsr0[$tFirst])) {
            foreach ($this->prefixesPsr0[$tFirst] as $prefix => $tDirectories) {
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
        foreach ($this->fallbackDirsPsr0 as $tDirectory) {
            if (file_exists($tFile = "{$tDirectory}/{$tLogicalPathPsr0}")) {
                return $tFile;
            }
        }
    }
}

/**
 * Scope isolated include
 *
 * Prevents access to $this/self from included files
 */
function composerIncludeFile($uFile)
{
    include $uFile;
}
