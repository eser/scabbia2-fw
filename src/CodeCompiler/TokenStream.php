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

namespace Scabbia\CodeCompiler;

use Countable;
use Exception;
use SeekableIterator;
use OutOfBoundsException;

/**
 * Generator
 *
 * @package     Scabbia\CodeCompiler
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class TokenStream implements Countable, SeekableIterator
{
    /** @type array $tokens set of tokens */
    public $tokens = [];
    /** @type int   $position */
    public $position = 0;


    /**
     * Initializes a token stream
     *
     * @param array $uTokens    set of tokens
     *
     * @return TokenStream
     */
    public function __construct(array $uTokens = [])
    {
        foreach ($uTokens as $tToken) {
            if (is_array($tToken)) {
                $this->tokens[] = [$tToken[0], $tToken[1]];
            } else {
                $this->tokens[] = [null, $tToken];
            }
        }
    }

    /**
     * Count elements of an object
     *
     * @return int the count as an integer
     */
    public function count()
    {
        return count($this->tokens);
    }

    /**
     * Seeks to a position
     *
     * @param int $uPosition the position to seek to
     *
     * @throws OutOfBoundsException
     * @return void
     */
    public function seek($uPosition)
    {
        if (!isset($this->tokens[$uPosition])) {
            throw new OutOfBoundsException("invalid seek position ($uPosition)");
        }

        $this->position = $uPosition;
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Return the current element
     *
     * @return mixed
     */
    public function current()
    {
        return $this->tokens[$this->position];
    }

    /**
     * Return the key of the current element
     *
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Checks if current position is valid
     *
     * @return bool true on success or false on failure
     */
    public function valid()
    {
        return isset($this->tokens[$this->position]);
    }

    /**
     * Move back to previous element
     *
     * @return mixed
     */
    public function prev()
    {
        $this->position--;
    }

    /**
     * Gets next token if condition is true
     *
     * @param  array|integer     $uType
     * @param  array|string|null $uValue
     *
     * @return bool
     */
    public function nextIf($uType, $uValue = null)
    {
        if ($this->test($uType, $uValue)) {
            $this->position++;
            return true;
        }

        return false;
    }

    /**
     * Advances until a token with the given type is found
     *
     * @param  array|integer     $uType
     * @param  array|string|null $uValue
     *
     * @return mixed
     */
    public function nextUntil($uType, $uValue = null)
    {
        while (!$this->test($uType, $uValue)) {
            $this->position++;

            if (!isset($this->tokens[$this->position])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tests the current token for a condition
     *
     * @param  array|integer     $uType
     * @param  array|string|null $uValue
     *
     * @return mixed
     */
    public function test($uType, $uValue = null)
    {
        $tToken = $this->tokens[$this->position];

        if ($uValue !== null) {
            if (is_array($uValue) && !in_array($tToken[1], $uValue)) {
                return false;
            }

            if ($tToken[1] !== $uValue) {
                return false;
            }
        }

        if ($tToken[0] !== $uType) {
            return false;
        }

        return true;
    }

    /**
     * Tests the current token for a condition or throws an exception otherwise
     *
     * @param  array|integer     $uType
     * @param  array|string|null $uValue
     * @param  string|null       $uMessage
     *
     * @throws Exception
     * @return void
     */
    public function expect($uType, $uValue = null, $uMessage = null)
    {
        if (!$this->test($uType, $uValue)) {
            $tToken = $this->tokens[$this->position];

            throw new Exception(sprintf(
                "%sUnexpected token \"%s\" of value \"%s\" (\"%s\" expected%s)",
                $uMessage ? "{$uMessage}. " : "",
                token_name($tToken[0]),
                $tToken[1],
                token_name($uType),
                ($uValue ? sprintf(" with value \"%s\"", $uValue) : "")
            ));
        }
    }
}
