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

/**
 * Delegate is an inline members which executes an event-chain execution similar to Events,
 * but designed for object-oriented architecture
 *
 * @package     Scabbia\Events
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class Delegate
{
    /** @type array   list of callbacks */
    public $callbacks = null;
    /** @type mixed   expected return value for interruption */
    public $expectedReturn;


    /**
     * Constructs a new delegate in order to assign it to a member
     *
     * @param mixed $uExpectedReturn Expected return value for interruption
     *
     * @return Delegate a delegate
     */
    public static function assign($uExpectedReturn = false)
    {
        $tNewInstance = new static($uExpectedReturn);

        return function (/* callable */ $uCallback = null, $uState = null, $uPriority = 10) use ($tNewInstance) {
            if ($uCallback !== null) {
                $tNewInstance->add($uCallback, $uState, $uPriority);
            }

            return $tNewInstance;
        };
    }

    /**
     * Unserializes an instance of delegate
     *
     * @param array $uPropertyBag properties set of unserialized object
     *
     * @return Delegate a delegate
     */
    public static function __set_state(array $uPropertyBag)
    {
        $tNewInstance = new static($uPropertyBag["expectedReturn"]);
        $tNewInstance->callbacks = $uPropertyBag["callbacks"];

        return $tNewInstance;
    }

    /**
     * Constructs a new instance of delegate
     *
     * @param mixed $uExpectedReturn Expected return value for interruption
     *
     * @return Delegate
     */
    public function __construct($uExpectedReturn = false)
    {
        $this->expectedReturn = $uExpectedReturn;
    }

    /**
     * Adds a callback to delegate
     *
     * @param callback  $uCallback  callback method
     * @param mixed     $uState     state object
     * @param null|int  $uPriority  priority level
     *
     * @return void
     */
    public function add(/* callable */ $uCallback, $uState = null, $uPriority = null)
    {
        // TODO: SplPriorityQueue has a problem with serialization
        /*
        if ($this->callbacks === null) {
            $this->callbacks = new \SplPriorityQueue();
        }

        if ($uPriority === null) {
            $uPriority = 10;
        }

        $this->callbacks->insert([$uCallback, $uState], $uPriority);
        */

        if ($this->callbacks === null) {
            $this->callbacks = [];
        }

        $this->callbacks[] = [$uCallback, $uState];
    }

    /**
     * Invokes the event-chain execution
     *
     * @return bool whether the execution is broken or not
     */
    public function invoke()
    {
        $tArgs = func_get_args();

        if ($this->callbacks !== null) {
            foreach ($this->callbacks as $tCallback) {
                $tEventArgs = $tArgs;
                array_unshift($tEventArgs, $tCallback[1]);

                if (call_user_func_array($tCallback[0], $tEventArgs) === $this->expectedReturn) {
                    return false;
                }
            }
        }

        return true;
    }
}
