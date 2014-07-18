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

/**
 * PSR-4 Autoloader Class
 *
 * An example of a general-purpose implementation that includes the optional
 * functionality of allowing multiple base directories for a single namespace
 * prefix
 *
 * Given a foo-bar package of classes in the file system at the following
 * paths ...
 *
 *     /path/to/packages/foo-bar/
 *         src/
 *             Baz.php             # Foo\Bar\Baz
 *             Qux/
 *                 Quux.php        # Foo\Bar\Qux\Quux
 *         tests/
 *             BazTest.php         # Foo\Bar\BazTest
 *             Qux/
 *                 QuuxTest.php    # Foo\Bar\Qux\QuuxTest
 *
 * ... add the path to the class files for the \Foo\Bar\ namespace prefix
 * as follows:
 *
 *      <?php
 *      // instantiate and register the loader
 *      $loader = new \Scabbia\Loader\Psr4();
 *      $loader->register();
 *
 *      // register the base directories for the namespace prefix
 *      $loader->addNamespace("Foo\\Bar", "/path/to/packages/foo-bar/src");
 *      $loader->addNamespace("Foo\\Bar", "/path/to/packages/foo-bar/tests");
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\Quux class from /path/to/packages/foo-bar/src/Qux/Quux.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\Quux();
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\QuuxTest class from /path/to/packages/foo-bar/tests/Qux/QuuxTest.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\QuuxTest();
 *
 * @package     Scabbia\Loaders
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 */
class Psr4
{
    /**
     * An associative array where the key is a namespace prefix and the value is an array of base directories
     * for classes in that namespace
     *
     * @type array
     */
    protected $prefixes = [];


    /**
     * Initializes Psr4 autoloader and registers it
     *
     * @return Psr4 the instance
     */
    public static function init()
    {
        $tInstance = new static();

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
     * Adds a base directory for a namespace prefix
     *
     * @param string $uPrefix   the namespace prefix
     * @param string $uBasePath a base directory for class files in the namespace
     * @param bool   $uPrepend  if true, prepend the base directory to the stack instead of appending it; this causes
     *                          it to be searched first rather than last
     *
     * @return void
     */
    public function addNamespace($uPrefix, $uBasePath, $uPrepend = false)
    {
        // normalize namespace prefix
        $uPrefix = trim($uPrefix, "\\") . "\\";

        // normalize the base directory with a trailing separator
        $uBasePath = rtrim($uBasePath, "/" . DIRECTORY_SEPARATOR) . "/";

        // initialize the namespace prefix array
        if (isset($this->prefixes[$uPrefix]) === false) {
            $this->prefixes[$uPrefix] = [];
        }

        // retain the base directory for the namespace prefix
        if ($uPrepend) {
            array_unshift($this->prefixes[$uPrefix], $uBasePath);
        } else {
            array_push($this->prefixes[$uPrefix], $uBasePath);
        }
    }

    /**
     * Loads the class file for a given class name
     *
     * @param string $uClass the fully-qualified class name
     *
     * @return mixed The mapped file name on success, or boolean false on failure
     */
    public function loadClass($uClass)
    {
        // the current namespace prefix
        $tPrefix = $uClass;

        // work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while (($tPos = strrpos($tPrefix, "\\")) !== false) {

            // retain the trailing namespace separator in the prefix
            $tPrefix = substr($uClass, 0, $tPos + 1);

            // the rest is the relative class name
            $tRelativeClass = substr($uClass, $tPos + 1);

            // try to load a mapped file for the prefix and relative class
            $tMappedFile = $this->loadMappedFile($tPrefix, $tRelativeClass);
            if ($tMappedFile) {
                return $tMappedFile;
            }

            // remove the trailing namespace separator for the next iteration
            // of strrpos()
            $tPrefix = rtrim($tPrefix, "\\");
        }

        // never found a mapped file
        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class
     *
     * @param string $uPrefix        the namespace prefix
     * @param string $uRelativeClass the relative class name
     *
     * @return mixed boolean false if no mapped file can be loaded, or the name of the mapped file that was loaded
     */
    protected function loadMappedFile($uPrefix, $uRelativeClass)
    {
        // are there any base directories for this namespace prefix?
        if (isset($this->prefixes[$uPrefix]) === false) {
            return false;
        }

        // look through base directories for this namespace prefix
        foreach ($this->prefixes[$uPrefix] as $uBasePath) {

            // replace the namespace prefix with the base directory,
            // replace namespace separators with directory separators
            // in the relative class name, append with .php
            $tFile = $uBasePath
                . str_replace("\\", "/", $uRelativeClass)
                . ".php";

            // if the mapped file exists, require it
            if (file_exists($tFile)) {
                psr4IncludeFile($tFile);
                return true;
            }
        }

        // never found it
        return false;
    }
}

/**
 * Scope isolated include
 *
 * Prevents access to $this/self from included files
 */
function psr4IncludeFile($uFile)
{
    include $uFile;
}
