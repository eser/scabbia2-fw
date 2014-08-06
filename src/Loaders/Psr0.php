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
 * PSR-0 Autoloader Class
 *
 * An example of a general-purpose implementation that includes the optional
 * functionality of allowing multiple base directories for all namespaces
 *
 * Given a foo-bar package of classes in the file system at the following
 * paths ...
 *
 *     /path/to/src/
 *         Foo/
 *             Bar/
 *                 Baz.php             # Foo\Bar\Baz
 *                 BazTest.php         # Foo\Bar\BazTest
 *                 Qux/
 *                     Quux.php        # Foo\Bar\Qux\Quux
 *                     QuuxTest.php    # Foo\Bar\Qux\QuuxTest
 *
 * ... add the path to the class files as follows:
 *
 *      <?php
 *      // instantiate and register the loader
 *      $loader = new \Scabbia\Loader\Psr0();
 *      $loader->register();
 *
 *      // register the base directories
 *      $loader->addPath("/path/to/src");
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\Quux class from /path/to/src/Foo/Bar/Qux/Quux.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\Quux();
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\QuuxTest class from /path/to/src/Foo/Bar/Qux/QuuxTest.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\QuuxTest();
 *
 * @package     Scabbia\Loaders
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.5.0
 */
class Psr0
{
    /**
     * An array of base directories for classes in all namespaces
     *
     * @type array
     */
    protected $paths = [];


    /**
     * Initializes Psr0 autoloader and registers it
     *
     * @return Psr0 the instance
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
     * Adds a base directory
     *
     * @param string|array $uBasePath a base directory for class files
     * @param bool         $uPrepend  if true, prepend the base directory to the stack instead of appending it; this
     *                                causes it to be searched first rather than last
     *
     * @return void
     */
    public function addPath($uBasePath, $uPrepend = false)
    {
        if ($uPrepend) {
            $this->paths = array_merge(
                (array)$uBasePath,
                $this->paths
            );
        } else {
            $this->paths = array_merge(
                $this->paths,
                (array)$uBasePath
            );
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
        $tFile = "";
        $tNamespace = "";
        if (($lastNsPos = strrpos($uClass, "\\")) !== false) {
            $tNamespace = substr($uClass, 0, $lastNsPos);
            $uClass = substr($uClass, $lastNsPos + 1);
            $tFile = str_replace("\\", "/", $tNamespace) . "/";
        }
        $tFile .= str_replace("_", "/", $uClass) . ".php";

        // if the file exists, require it
        foreach ($this->paths as $tPath) {
            if (file_exists($tPath . $tFile)) {
                psr0IncludeFile($tPath . $tFile);
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
function psr0IncludeFile($uFile)
{
    include $uFile;
}
