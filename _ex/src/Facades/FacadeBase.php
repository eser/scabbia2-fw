<?php
/**
 * Scabbia2 PHP Framework Code
 * https://github.com/eserozvataf/scabbia2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/eserozvataf/scabbia2-fw for the canonical source repository
 * @copyright   2010-2016 Eser Ozvataf. (http://eser.ozvataf.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Facades;

use Scabbia\Helpers\Runtime;
use UnexpectedValueException;

/**
 * Default methods needed for implementation of a facade interface
 *
 * @package     Scabbia\Facades
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 */
abstract class FacadeBase
{
    /** @type array $callbackMap  map for callbacks */
    public static $callbackMap;


    /**
     * Handles static method calls to the class definition
     *
     * @param string $uMethod     method name
     * @param array  $uParameters parameters
     *
     * @throws UnexpectedValueException if mapped method not found
     * @return mixed
     */
    public static function __callStatic($uMethod, $uParameters)
    {
        if (isset(static::$callbackMap[$uMethod])) {
            return call_user_func(
                Runtime::callbacks(static::$callbackMap[$uMethod]),
                ...$uParameters
            );
        }

        // TODO exception
        throw new UnexpectedValueException("");
    }
}
