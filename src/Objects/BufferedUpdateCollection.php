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

namespace Scabbia\Objects;

/**
 * BufferedUpdateCollection
 *
 * @package     Scabbia\Objects
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.5.0
 */
class BufferedUpdateCollection implements \ArrayAccess, \IteratorAggregate, \Serializable
{
    /** @type array items */
    public $items;
    /** @type array queue */
    public $queue;
    /** @type callable update function */
    public $updateFunc;


    /**
     * Initializes a BufferedUpdateCollection class instance
     *
     * @param callable $uUpdateFunc update function
     * @param array    $uArray      initial items
     *
     * @return BufferedUpdateCollection
     */
    public function __construct(/* callable */ $uUpdateFunc, array $uArray = [])
    {
        $this->updateFunc = $uUpdateFunc;
        $this->items = $uArray;
        $this->queue = [];
    }

    /**
     * Enqueues given keys into collection to get all elements at the same time
     *
     * @param string $uKey key for element
     *
     * @return void
     */
    public function enqueue($uKey)
    {
        foreach ((array)$uKey as $tKey) {
            if (in_array($tKey, $this->queue, true) || $this->keyExists($tKey)) {
                continue;
            }

            $this->queue[] = $tKey;
        }
    }

    /**
     * Updates the collection by executing update function
     *
     * @return void
     */
    public function update()
    {
        if ($this->updateFunc === null) {
            return;
        }

        if (count($this->queue) === 0) {
            return;
        }

        $this->items += call_user_func($this->updateFunc, $this->queue);
        $this->queue = [];
    }

    /**
     * Gets the specified element in the collection
     *
     * @param mixed $uKey     key for element
     * @param mixed $uDefault default value if the key does not exist
     *
     * @return mixed the element or the default
     */
    public function get($uKey, $uDefault = null)
    {
        if (in_array($uKey, $this->queue, true)) {
            $this->update();
        }

        if (array_key_exists($uKey, $this->items)) {
            return $this->items[$uKey];
        }

        return $uDefault;
    }

    /**
     * Gets the set of specified elements in the collection
     *
     * @param mixed $uKeys   keys for elements
     *
     * @return mixed the set of elements
     */
    public function getRange(array $uKeys)
    {
        $tItems = [];

        foreach ($uKeys as $tKey) {
            if (in_array($tKey, $this->queue, true)) {
                $this->update();
            }

            if (array_key_exists($tKey, $this->items)) {
                $tItems[$tKey] = $this->items[$tKey];
            }
        }

        return $tItems;
    }

    /**
     * Enqueues the keys then updates the collection in order
     * to get requested elements back
     *
     * @param mixed $uKeys   keys for elements
     *
     * @return mixed the set of elements
     */
    public function enqueueAndGetRange(array $uKeys)
    {
        $this->enqueue($uKeys);
        return $this->getRange($uKeys);
    }
}
