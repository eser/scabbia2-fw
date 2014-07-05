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

use ArrayIterator;

/**
 * Collection
 *
 * @package     Scabbia\Objects
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.1.0
 */
class Collection implements \ArrayAccess, \IteratorAggregate, \Serializable
{
    /** @type array items */
    public $items;


    /**
     * Initializes a Collection class instance
     *
     * @param array $uArray initial items
     *
     * @return Collection
     */
    public function __construct(array $uArray = [])
    {
        $this->items = $uArray;
    }

    /**
     * Adds a new element into the collection
     *
     * @param mixed $uItem element to be added
     *
     * @return void
     */
    public function addItem($uItem)
    {
        $this->items[] = $uItem;
    }

    /**
     * Adds a new element into the collection
     *
     * @param mixed $uItem reference of the element to be added
     *
     * @return void
     */
    public function addItemRef(&$uItem)
    {
        $this->items[] = &$uItem;
    }

    /**
     * Adds a new element into the collection if it is
     * not exist in the collection already
     *
     * @param mixed $uItem element to be added
     *
     * @return bool if element is added
     */
    public function addItemUnique($uItem)
    {
        if (in_array($uItem, $this->items, true)) {
            return false;
        }

        $this->items[] = $uItem;
        return true;
    }

    /**
     * Adds a set of element into the collection
     *
     * @param array $uItems set of elements
     *
     * @return void
     */
    public function addItemRange($uItems)
    {
        $this->items += $uItems;
    }

    /**
     * Checks the element key if it is exist in the collection
     *
     * @param mixed $uKey  key for element
     *
     * @return bool if key exists in the collection
     */
    public function keyExists($uKey)
    {
        return array_key_exists($uKey, $this->items);
    }

    /**
     * Checks the element if it is exist in the collection
     *
     * @param mixed $uItem  element to be checked
     *
     * @return bool if element exists in the collection
     */
    public function containsItem($uItem)
    {
        return in_array($uItem, $this->items, true);
    }

    /**
     * Returns the number of elements in the collection
     *
     * @return int count of elements
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Returns the count of collection
     *
     * @param callable $uCallback callback function/method
     *
     * @return int count
     */
    public function countWalk(/* callable */ $uCallback)
    {
        $tCounted = 0;
        foreach ($this->items as &$tItem) {
            $tCounted += call_user_func($uCallback, $tItem);
        }

        return $tCounted;
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
            if (array_key_exists($tKey, $this->items)) {
                $tItems[$tKey] = $this->items[$tKey];
            }
        }

        return $tItems;
    }

    /**
     * Sets a specified key into the collection
     *
     * @param mixed $uKey  key for element
     * @param mixed $uItem element to be set
     *
     * @return void
     */
    public function set($uKey, $uItem)
    {
        $this->items[$uKey] = $uItem;
    }

    /**
     * Sets a specified key into the collection
     *
     * @param mixed $uKey  key for element
     * @param mixed $uItem reference of the element to be set
     *
     * @return void
     */
    public function setRef($uKey, &$uItem)
    {
        $this->items[$uKey] = &$uItem;
    }

    /**
     * Sets a specified key into the collection
     *
     * @param mixed $uItems set of elements to be set
     *
     * @return void
     */
    public function setRange(array $uItems)
    {
        foreach ($uItems as $tKey => $tValue) {
            $this->items[$tKey] = $tValue;
        }
    }

    /**
     * Removes specified elements from the collection
     *
     * @param mixed $uKey key for element
     *
     * @return int count of removed elements
     */
    public function remove($uKey)
    {
        if (!isset($this->items[$uKey])) {
            return 0;
        }

        unset($this->items[$uKey]);
        return 1;
    }

    /**
     * Removes elements from the collection
     *
     * @param mixed $uItem  element
     * @param int   $uLimit limit
     *
     * @return int count of removed elements
     */
    public function removeItem($uItem, $uLimit = 0)
    {
        $tRemoved = 0;

        while (($uLimit === 0 || $tRemoved < $uLimit) &&
            ($tKey = array_search($uItem, $this->items, true)) !== false) {
            unset($this->items[$tKey]);
        }

        return $tRemoved;
    }

    /**
     * Removes the element at specified index on the collection
     *
     * @param int $uIndex index of element
     *
     * @return int count of removed elements
     */
    public function removeIndex($uIndex)
    {
        if ($uIndex >= count($this->items)) {
            return 0;
        }

        for ($i = 0, reset($this->items); $i < $uIndex; $i++, next($this->items)) {
        }

        unset($this->items[key($this->items)]);
        return 1;
    }

    /**
     * Pops the last item in the collection
     *
     * @return mixed the last element
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Pushes an element into the collection
     *
     * @param mixed $uItem the element to be pushed
     */
    public function push($uItem)
    {
        $this->items[] = $uItem;
    }

    /**
     * Pops the first item in the collection
     *
     * @return mixed the first element
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Inserts an element into the beginning of the collection
     *
     * @param mixed $uItem the element to be inserted
     *
     * @return void
     */
    public function unshift($uItem)
    {
        array_unshift($this->items, $uItem);
    }

    /**
     * Gets the first element of the collection
     *
     * @return mixed the first element
     */
    public function first()
    {
        reset($this->items);

        return $this->current();
    }

    /**
     * Gets the last element of the collection
     *
     * @return mixed the last element
     */
    public function last()
    {
        return end($this->items);
    }

    /**
     * Gets the element at the current position for the iterator
     *
     * @return mixed current element
     */
    public function current()
    {
        $tValue = current($this->items);

        if ($tValue === false) {
            return null;
        }

        return $tValue;
    }

    /**
     * Gets the element at the next position for the iterator
     *
     * @return mixed next element
     */
    public function next()
    {
        $tValue = $this->current();
        next($this->items);

        return $tValue;
    }

    /**
     * Clears the contents of the collection
     *
     * @return void
     */
    public function clear()
    {
        $this->items = [];
        // $this->internalIterator->rewind();
    }

    /**
     * Applies a method call to all elements in the collection
     *
     * @param callable $uCallback  callback function/method
     * @param bool     $uRecursive call the method recursively
     *
     * @return void
     */
    public function walk(/* callable */ $uCallback, $uRecursive = false)
    {
        if ($uRecursive) {
            array_walk_recursive($this->items, $uCallback);
            return;
        }

        array_walk($this->items, $uCallback);
    }

    // for array access, $items
    /**
     * Whether a offset exists
     *
     * @param mixed $uId An offset to check for
     *
     * @return boolean true on success or false on failure
     */
    public function offsetExists($uId)
    {
        return $this->keyExists($uId);
    }

    /**
     * Offset to retrieve
     *
     * @param mixed $uId The offset to retrieve
     *
     * @return mixed Can return all value types
     */
    public function offsetGet($uId)
    {
        return $this->get($uId);
    }

    /**
     * Offset to set
     *
     * @param mixed $uId    The offset to assign the value to
     * @param mixed $uValue The value to set
     *
     * @return void
     */
    public function offsetSet($uId, $uValue)
    {
        $this->set($uId, $uValue);
    }

    /**
     * Offset to unset
     *
     * @param mixed $uId The offset to unset
     *
     * @return void
     */
    public function offsetUnset($uId)
    {
        $this->removeKey($uId);
    }

    // for iteration access
    /**
     * Retrieve an external iterator
     *
     * @return Traversable An instance of an object implementing Iterator or Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    // for serialization
    /**
     * String representation of object
     *
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize($this->items);
    }

    /**
     * Constructs the object
     *
     * @param string $uSerialized The string representation of the object
     *
     * @return void
     */
    public function unserialize($uSerialized)
    {
        // TODO check http://php.net/manual/en/serializable.unserialize.php for correct behavior
        $this->items = unserialize($uSerialized);
    }

    /**
     * Creates a new collection instance with existing elements
     *
     * @return Collection the new instance
     */
    public function toCollection()
    {
        return new static($this->items);
    }

    /**
     * Returns the existing elements in an array
     *
     * @return array set existing of elements
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * Returns the reference of inner array
     *
     * @return array the reference of collection's inner array
     */
    public function &toArrayRef()
    {
        return $this->items;
    }

    /**
     * Joins the set of elements as a string
     *
     * @param string $uSeparator the glue string
     *
     * @return string joined elements
     */
    public function toString($uSeparator = "")
    {
        return implode($uSeparator, $this->items);
    }
}
