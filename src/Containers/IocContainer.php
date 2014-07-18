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

// use ReflectionClass;

/**
 * IocContainer
 *
 * @package     Scabbia\Containers
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
trait IocContainer
{
    /** @type array $serviceParameters service parameters */
    public $serviceParameters = [];
    /** @type array $serviceDefinitions ioc definitions */
    protected $serviceDefinitions = [];
    /** @type array $sharedServiceObjects shared service objects */
    protected $sharedServiceObjects = [];


    /**
     * Sets a service definition
     *
     * @param string   $uName             name of the service
     * @param callable $uCallback         callback
     * @param bool     $uIsSharedInstance is it a shared instance
     *
     * @return void
     */
    public function setService($uName, /* callable */ $uCallback, $uIsSharedInstance = true)
    {
        $this->serviceDefinitions[$uName] = [$uCallback, $uIsSharedInstance];
    }

    /**
     * Sets a shared service object
     *
     * @param string   $uName             name of the service
     * @param mixed    $uObject           object instance
     *
     * @return void
     */
    public function setSharedServiceObject($uName, $uObject)
    {
        $this->sharedServiceObjects[$uName] = $uObject;
    }

    /**
     * Magic method for inversion of control containers
     *
     * @param string $uName name of the service
     *
     * @return mixed the service instance
     */
    public function __get($uName)
    {
        if (array_key_exists($uName, $this->sharedServiceObjects)) {
            return $this->sharedServiceObjects[$uName];
        }

        $tService = $this->serviceDefinitions[$uName];
        // if (is_a($tService[0], "Closure")) {
            $tReturn = call_user_func($tService[0], $this->serviceParameters);
        // } else {
        //     $tReturn = (new ReflectionClass($tService[0]))->newInstanceArgs($this->serviceParameters);
        // }

        if ($tService[1] === true) {
            $this->sharedServiceObjects[$uName] = $tReturn;
        }

        return $tReturn;
    }
}
