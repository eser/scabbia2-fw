<?php
/**
 * Scabbia2 PHP Framework
 * https://github.com/eserozvataf/scabbia2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/eserozvataf/scabbia2 for the canonical source repository
 * @copyright   2010-2016 Eser Ozvataf. (http://eser.ozvataf.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace MyProject\FrontModule\Processors;

use Scabbia\Helpers\String;

/**
 * Events class
 *
 * @package     MyProject\FrontModule\Processors
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 */
class Events
{
    /**
     * A sample for event-member method for applicationInit event
     *
     * @event {on: applicationInit, priority: 10}
     *
     * @return void
    */
    public static function onLoad()
    {
        // echo "onApplicationInit<br />";
    }

    /**
     * A sample for event-member method for requestBegin event
     *
     * @event {on: requestBegin, priority: 10}
     *
     * @param null|array $uEventArgs arguments for the event
     *
     * @return void
     */
    public static function onRequestBegin($uEventArgs)
    {
        // echo "onRequestBegin<br />";

        String::vardump($uEventArgs);
    }

    /**
     * A sample for event-member method for requestEnd event
     *
     * @event {on: requestEnd, priority: 10}
     *
     * @param null|array $uEventArgs arguments for the event
     *
     * @return void
     */
    public static function onRequestEnd($uEventArgs)
    {
        // echo "onRequestEnd<br />";

        $tDiff = (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]);
        echo "Generated in {$tDiff} msec";
    }
}
