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

use Exception;

/**
 * SingletonContainer
 *
 * @package     Scabbia\Containers
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
trait SingletonContainer
{
    /** @type object $instance singleton object */
    public static $instance = null;


    /**
     * Gets the singleton object
     *
     * @return object class instance
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Sets the class instance as singleton object
     *
     * @return void
     */
    public function setDefaultInstance()
    {
        static::$instance = $this;
    }

    /**
     * Constructor to prevent new instances of this class
     *
     * @return SingletonContainer
     */
    final private function __construct()
    {
    }

    /**
     * Clone method to prevent duplication of this class
     *
     * @return SingletonContainer
     */
    final private function __clone()
    {
    }

    /**
     * Unserialization method to prevent restoration of this class
     *
     * @return SingletonContainer
     */
    final private function __wakeup()
    {
    }
}
