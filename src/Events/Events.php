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

namespace Scabbia\Events;

use Scabbia\Framework\Core;
use Scabbia\Events\Delegate;

/**
 * Events
 *
 * @package     Scabbia\Events
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class Events
{
    /** @type null|array event subscribers */
    public static $events = null;
    /** @type array      event depth */
    public static $eventDepth = array();
    /** @type bool       indicates the event manager is currently disabled or not */
    public static $disabled = false;


    /**
     * Invokes an event
     *
     * @param string     $uEvent     name of the event
     * @param null|array $uEventArgs arguments for the event
     *
     * @return bool whether the event is invoked or not
     */
    public static function invoke($uEvent, $uEventArgs = null)
    {
        if (self::$events === null) {
            $tEventsFilePath = Core::$basepath . "/writable/generated/events.php";
            self::$events = require $tEventsFilePath;
        }

        if (self::$disabled) {
            return null;
        }

        if (!isset(self::$events[$uEvent])) {
            return null;
        }

        self::$eventDepth[] = [$uEvent, $uEventArgs];
        $tReturn = self::$events[$uEvent]->invoke($uEventArgs);
        array_pop(self::$eventDepth);

        return $tReturn;
    }

    /**
     * Makes a callback method subscribed to specified event
     *
     * @param string   $uEvent    event
     * @param callable $uCallback callback
     * @param mixed    $uState    state object
     * @param null|int $uPriority priority
     *
     * @return void
     */
    public static function register($uEvent, $uCallback, $uState, $uPriority = null)
    {
        if (self::$events === null) {
            $tEventsFilePath = Core::$basepath . "/writable/generated/events.php";
            self::$events = require $tEventsFilePath;
        }

        if (!isset(self::$events[$uEvent])) {
            self::$events[$uEvent] = new Delegate();
        }

        self::$events[$uEvent]->add($uCallback, $uState, $uPriority);
    }
}
