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

use InvalidArgumentException;

/**
 * Loader implements a PSR-4 and PSR-4 class loader
 *
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
 *
 * This class is loosely based on the Symfony UniversalClassLoader.
 *
 * @package     Scabbia\Loaders
 * @author      Fabien Potencier <fabien@symfony.com>
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class Loader
{
    // PSR-4
    protected $prefixLengthsPsr4 = [[], []];
    protected $prefixDirsPsr4 = [[], []];
    protected $fallbackDirsPsr4 = [[], []];

    // PSR-0
    protected $prefixesPsr0 = [[], []];
    protected $fallbackDirsPsr0 = [[], []];

    protected $classMap = [];
    protected $pushStack = [];


    /**
     * Initializes the autoloader and registers it
     *
     * @param string|null $uComposerPath the path of composer files installed in
     *
     * @return Loader the instance
     */
    public static function init($uComposerPath = null)
    {
        $tInstance = new static();
        if ($uComposerPath !== null) {
            $tInstance->importFromComposer($uComposerPath);
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
     * @return array
     */
    public function getPrefixesPsr0()
    {
        return call_user_func("array_merge", ...$this->prefixesPsr0[1]);
    }

    /**
     * Gets prepended prefixes for PSR-0
     *
     * @return array
     */
    public function getPrependedPrefixesPsr0()
    {
        return call_user_func("array_merge", ...$this->prefixesPsr0[0]);
    }

    /**
     * Gets prefixes for PSR-4
     *
     * @return array
     */
    public function getPrefixesPsr4()
    {
        return $this->prefixDirsPsr4[1];
    }

    /**
     * Gets prepended prefixes for PSR-4
     *
     * @return array
     */
    public function getPrependedPrefixesPsr4()
    {
        return $this->prefixDirsPsr4[0];
    }

    /**
     * Gets fallback directories for PSR-0
     *
     * @return array
     */
    public function getFallbackDirsPsr0()
    {
        return $this->fallbackDirsPsr0[1];
    }

    /**
     * Gets prepended fallback directories for PSR-0
     *
     * @return array
     */
    public function getPrependedFallbackDirsPsr0()
    {
        return $this->fallbackDirsPsr0[0];
    }

    /**
     * Gets fallback directories for PSR-4
     *
     * @return array
     */
    public function getFallbackDirsPsr4()
    {
        return $this->fallbackDirsPsr4[1];
    }

    /**
     * Gets prepended fallback directories for PSR-4
     *
     * @return array
     */
    public function getPrependedFallbackDirsPsr4()
    {
        return $this->fallbackDirsPsr4[0];
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
     * @param string|null $uComposerPath the path of composer files installed in
     *
     * @return void
     */
    public function importFromComposer($uComposerPath)
    {
        $tMap = require "{$uComposerPath}/autoload_namespaces.php";
        foreach ($tMap as $tNamespace => $tPath) {
            $this->setPsr0($tNamespace, $tPath);
        }

        $tMap = require "{$uComposerPath}/autoload_psr4.php";
        foreach ($tMap as $tNamespace => $tPath) {
            $this->setPsr4($tNamespace, $tPath);
        }

        $tClassMap = require "{$uComposerPath}/autoload_classmap.php";
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
     * @param bool         $uPrepend whether to prepend the directories
     *
     * @return void
     */
    public function addPsr0($uPrefix, $uPaths, $uPrepend = false)
    {
        $tIndex = ($uPrepend) ? 0 : 1;

        if (!$uPrefix) {
            $this->fallbackDirsPsr0[$tIndex] = array_merge($this->fallbackDirsPsr0[$tIndex], (array)$uPaths);
            return;
        }

        $tFirst = $uPrefix[0];
        if (!isset($this->prefixesPsr0[$tIndex][$tFirst][$uPrefix])) {
            $this->prefixesPsr0[$tIndex][$tFirst][$uPrefix] = (array)$uPaths;
        } else {
            $this->prefixesPsr0[$tIndex][$tFirst][$uPrefix] = array_merge(
                $this->prefixesPsr0[$tIndex][$tFirst][$uPrefix],
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
        $tIndex = ($uPrepend) ? 0 : 1;

        if (!$uPrefix) {
            $this->fallbackDirsPsr4[$tIndex] = array_merge($this->fallbackDirsPsr4[$tIndex], (array)$uPaths);
        } elseif (!isset($this->prefixDirsPsr4[$tIndex][$uPrefix])) {
            // Register directories for a new namespace.
            $tLength = strlen($uPrefix);

            if ($uPrefix[$tLength - 1] !== "\\") {
                throw new InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }

            $this->prefixLengthsPsr4[$tIndex][$uPrefix[0]][$uPrefix] = $tLength;
            $this->prefixDirsPsr4[$tIndex][$uPrefix] = (array)$uPaths;
        } else {
            // Append directories for an already registered namespace.
            $this->prefixDirsPsr4[$tIndex][$uPrefix] = array_merge(
                $this->prefixDirsPsr4[$tIndex][$uPrefix],
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
     * @param bool         $uPrepend whether to prepend the directories
     *
     * @return void
     */
    public function setPsr0($uPrefix, $uPaths, $uPrepend = false)
    {
        $tIndex = ($uPrepend) ? 0 : 1;

        if (!$uPrefix) {
            $this->fallbackDirsPsr0[$tIndex] = (array)$uPaths;
        } else {
            $this->prefixesPsr0[$tIndex][$uPrefix[0]][$uPrefix] = (array)$uPaths;
        }
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace,
     * replacing any others previously set for this namespace
     *
     * @param string       $uPrefix  the prefix/namespace, with trailing '\\'
     * @param array|string $uPaths   the PSR-4 base directories
     * @param bool         $uPrepend whether to prepend the directories
     *
     * @throws InvalidArgumentException
     * @return void
     */
    public function setPsr4($uPrefix, $uPaths, $uPrepend = false)
    {
        $tIndex = ($uPrepend) ? 0 : 1;

        if (!$uPrefix) {
            $this->fallbackDirsPsr4[$tIndex] = (array)$uPaths;
        } else {
            $tLength = strlen($uPrefix);

            if ($uPrefix[$tLength - 1] !== "\\") {
                throw new InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }

            $this->prefixLengthsPsr4[$tIndex][$uPrefix[0]][$uPrefix] = $tLength;
            $this->prefixDirsPsr4[$tIndex][$uPrefix] = (array)$uPaths;
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

        for ($tIndex = 0; $tIndex <= 1; $tIndex++) {
            // PSR-4 lookup
            if (isset($this->prefixLengthsPsr4[$tIndex][$tFirst])) {
                foreach ($this->prefixLengthsPsr4[$tIndex][$tFirst] as $prefix => $tLength) {
                    if (strpos($uClass, $prefix) === 0) {
                        foreach ($this->prefixDirsPsr4[$tIndex][$prefix] as $tDirectory) {
                            if (file_exists($tFile = "{$tDirectory}/" . substr($tLogicalPathPsr4, $tLength))) {
                                return $tFile;
                            }
                        }
                    }
                }
            }

            // PSR-4 fallback dirs
            foreach ($this->fallbackDirsPsr4[$tIndex] as $tDirectory) {
                if (file_exists($tFile = "{$tDirectory}/{$tLogicalPathPsr4}")) {
                    return $tFile;
                }
            }

            // PSR-0 lookup
            if (isset($this->prefixesPsr0[$tIndex][$tFirst])) {
                foreach ($this->prefixesPsr0[$tIndex][$tFirst] as $prefix => $tDirectories) {
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
            foreach ($this->fallbackDirsPsr0[$tIndex] as $tDirectory) {
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
