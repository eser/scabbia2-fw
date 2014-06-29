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
class Container
{
    /**
     * @ignore
     */
    public static $loaded = [];


    /**
     * @ignore
     */
    public static function load()
    {
        $tArgs = func_get_args();
        $tModelClass = array_shift($tArgs);

        if (is_object($tModelClass)) {
            $tModelClassName = get_class($tModelClass);

            if (!isset(self::$loaded[$tModelClassName])) {
                self::$loaded[$tModelClassName] = $tModelClass;
            }

            return self::$loaded[$tModelClassName];
        }

        if (!isset(self::$loaded[$tModelClass])) {
            // TODO call constructor w/ $tArgs
            self::$loaded[$tModelClass] = new $tModelClass ();
        }

        return self::$loaded[$tModelClass];
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
    public function bind()
    {
        $tArgs = func_get_args();
        $tModelClass = array_shift($tArgs);
        $tMemberName = array_shift($tArgs);

        if ($tMemberName === null) {
            if (is_object($tModelClass)) {
                $tModelClassName = get_class($tModelClass);
            } else {
                $tModelClassName = $tModelClass;
            }

            if (($tPos = strrpos($tModelClassName, "\\")) !== false) {
                $uMemberName = lcfirst(substr($tModelClassName, $tPos + 1));
            } else {
                $uMemberName = lcfirst($tModelClassName);
            }
        }

        $this->{$tMemberName} = call_user_func_array("Container::load", $tArgs);
    }
}
