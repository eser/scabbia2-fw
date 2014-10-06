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

namespace Scabbia\Containers;

use ReflectionClass;

/**
 * BindableContainer
 *
 * @package     Scabbia\Containers
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
trait BindableContainer
{
    /** @type array $loadedObjects loaded objects */
    public static $loadedObjects = [];
    /** @type array $sharedBindings shared bindings registry */
    public static $sharedBindings = [];


    /**
     * Loads and stores a class
     *
     * @param string|object $uClass      object or class name
     * @param array         $uParameters parameters
     *
     * @return object class instance
     */
    public static function load($uClass, array $uParameters = [])
    {
        if (is_object($uClass)) {
            $tClassName = get_class($uClass);

            if (!isset(BindableContainer::$loadedObjects[$tClassName])) {
                BindableContainer::$loadedObjects[$tClassName] = $uClass;
            }

            return BindableContainer::$loadedObjects[$tClassName];
        }

        if (!isset(BindableContainer::$loadedObjects[$uClass])) {
            BindableContainer::$loadedObjects[$uClass] = new $uClass (...$uParameters);
        }

        return BindableContainer::$loadedObjects[$uClass];
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

    /**
     * Magic method for bindable containers
     *
     * @param string $uName name of the shared object
     *
     * @return mixed the shared object
     */
    public function __get($uName)
    {
        if (!array_key_exists($uName, static::$sharedBindings)) {
            return null;
        }

        $tSharedBinding = (array)static::$sharedBindings[$uName];

        if (count($tSharedBinding) === 1) {
            $tReturn = BindableContainer::load($tSharedBinding[0]);
        } else {
            $tReturn = BindableContainer::load($tSharedBinding[0], $tSharedBinding[1]);
        }

        $this->{$uName} = $tReturn;

        return $tReturn;
    }
}
