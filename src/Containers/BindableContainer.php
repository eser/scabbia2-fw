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

namespace Scabbia\Containers;

/**
 * BindableContainer
 *
 * @package     Scabbia\Containers
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
trait BindableContainer
{
    /** @type array $loaded loaded classes */
    public static $loaded = [];


    /**
     * Loads and stores a class
     *
     * @param string|object $uClass class
     * @param array         $uParameters parameters
     *
     * @return object class instance
     */
    public static function load($uClass, array $uParameters = [])
    {
        if (is_object($uClass)) {
            $tClassName = get_class($uClass);

            if (!isset(BindableContainer::$loaded[$tClassName])) {
                BindableContainer::$loaded[$tClassName] = $uClass;
            }

            return BindableContainer::$loaded[$tClassName];
        }

        if (!isset(BindableContainer::$loaded[$uClass])) {
            // TODO call constructor w/ $uParameters
            BindableContainer::$loaded[$uClass] = new $uClass ();
        }

        return BindableContainer::$loaded[$uClass];
    }

    /**
     * Binds a class instance to current context of the class
     *
     * @param string|object $uClass class
     * @param string|null   $uMemberName member name
     * @param array         $uParameters parameters
     *
     * @return void
     */
    public function bind($uClass, $uMemberName = null, array $uParameters = [])
    {
        if ($uMemberName === null) {
            if (is_object($uClass)) {
                $tClassName = get_class($uClass);
            } else {
                $tClassName = $uClass;
            }

            if (($tPos = strrpos($tClassName, "\\")) !== false) {
                $uMemberName = lcfirst(substr($tClassName, $tPos + 1));
            } else {
                $uMemberName = lcfirst($tClassName);
            }
        }

        $this->{$uMemberName} = BindableContainer::load($uClass, $uParameters);
    }
}
