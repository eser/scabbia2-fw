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

namespace Scabbia\Container;

/**
 * Container
 *
 * @package     Scabbia\Container
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
abstract class Container
{
    /**
     * @ignore
     */
    public static $loaded = [];


    /**
     * @ignore
     */
    public static function load($uModelClass, array $uParameters = [])
    {
        if (is_object($uModelClass)) {
            $tModelClassName = get_class($uModelClass);

            if (!isset(self::$loaded[$tModelClassName])) {
                self::$loaded[$tModelClassName] = $uModelClass;
            }

            return self::$loaded[$tModelClassName];
        }

        if (!isset(self::$loaded[$uModelClass])) {
            // TODO call constructor w/ $uParameters
            self::$loaded[$uModelClass] = new $uModelClass ();
        }

        return self::$loaded[$uModelClass];
    }

    /**
     * Initializes a Container class instance
     *
     * @return Container
     */
    public function __construct()
    {
        // TODO invoke event for creating a new container, so extensions can bind their instances
    }

    /**
     * @ignore
     */
    public function bind($uModelClass, $uMemberName = null, array $uParameters = [])
    {
        if ($uMemberName === null) {
            if (is_object($uModelClass)) {
                $tModelClassName = get_class($uModelClass);
            } else {
                $tModelClassName = $uModelClass;
            }

            if (($tPos = strrpos($tModelClassName, "\\")) !== false) {
                $uMemberName = lcfirst(substr($tModelClassName, $tPos + 1));
            } else {
                $uMemberName = lcfirst($tModelClassName);
            }
        }

        $this->{$uMemberName} = self::load($uModelClass, $uParameters);
    }
}
