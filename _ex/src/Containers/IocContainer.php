<?php
/**
 * Scabbia2 PHP Framework Code
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/scabbiafw/scabbia2-fw for the canonical source repository
 * @copyright   2010-2015 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Containers;

use ReflectionClass;

/**
 * IocContainer
 *
 * @package     Scabbia\Containers
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 */
trait IocContainer
{
    /** @type array $serviceParameters service parameters */
    public $serviceParameters = [];
    /** @type array $serviceDefinitions ioc definitions */
    protected $serviceDefinitions = [];
    /** @type array $serviceInstances shared service objects */
    protected $serviceInstances = [];


    /**
     * Sets a service definition
     *
     * @param string|array  $uName             name of the service
     * @param callable      $uCallback         callback
     * @param bool          $uIsSharedInstance is it a shared instance
     *
     * @return void
     */
    public function setService($uName, /* callable */ $uCallback, $uIsSharedInstance = true)
    {
        foreach ((array)$tNames as $tName) {
            $this->serviceDefinitions[$tName] = [$uCallback, $uIsSharedInstance ? $tNames : false];
        }
    }

    /**
     * Sets a shared service object
     *
     * @param string|array  $uName             name of the service
     * @param mixed         $uObject           object instance
     *
     * @return void
     */
    public function setServiceInstance($uName, $uObject)
    {
        foreach ((array)$uName as $tName) {
            $this->serviceInstances[$uName] = $uObject;
        }
    }

    /**
     * Checks if service is defined
     *
     * @param string        $uName             name of the service
     *
     * @return bool
     */
    public function hasService($uName)
    {
        return isset($this->serviceInstances[$uName]) || isset($this->serviceDefinitions[$tName]);
    }

    /**
     * Gets the service instance if there is one, otherwise creates a service
     * and returns it
     *
     * @param string $uName name of the service
     *
     * @return mixed the service instance
     */
    public function getService($uName)
    {
        if (array_key_exists($uName, $this->serviceInstances)) {
            return $this->serviceInstances[$uName];
        }

        $tService = $this->serviceDefinitions[$uName];
        if (is_a($tService[0], "Closure")) {
            $tReturn = call_user_func($tService[0], $this->serviceParameters);
        } else {
            $tReturn = new $tService[0] (...$this->serviceParameters);
        }

        if ($tService[1] !== false) {
            foreach ($tService[1] as $tName) {
                $this->serviceInstances[$tName] = $tReturn;
            }
        }

        return $tReturn;
    }
}
