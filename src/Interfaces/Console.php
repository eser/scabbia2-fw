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

namespace Scabbia\Interfaces;

use Scabbia\Interfaces\IInterface;

/**
 * Implementation of output to console
 *
 * @package     Scabbia\Interfaces
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 *
 * @todo add stdin features
 */
class Console implements IInterface
{
    /**
     * Writes given message in header format
     *
     * @param int    $uHeading size
     * @param string $uMessage message
     *
     * @return void
     */
    public function writeHeader($uHeading, $uMessage)
    {
        if ($uHeading === 1) {
            $tChar = "=";
        } else {
            $tChar = "-";
        }

        echo "{$uMessage}\r\n", str_repeat($tChar, strlen($uMessage)), "\r\n";

        if ($uHeading === 1) {
            echo "\r\n";
        }
    }

    /**
     * Writes given message in specified color
     *
     * @param string $uColor   color
     * @param string $uMessage message
     *
     * @return void
     */
    public function writeColor($uColor, $uMessage)
    {
        if (strncasecmp(PHP_OS, "WIN", 3) === 0) {
            echo "{$uMessage}\r\n";
            return;
        }

        if ($uColor === "black") {
            $tColor = "[0;30m";
        } elseif ($uColor === "darkgray") {
            $tColor = "[1;30m";
        } elseif ($uColor === "blue") {
            $tColor = "[0;34m";
        } elseif ($uColor === "lightblue") {
            $tColor = "[1;34m";
        } elseif ($uColor === "green") {
            $tColor = "[0;32m";
        } elseif ($uColor === "lightgreen") {
            $tColor = "[1;32m";
        } elseif ($uColor === "cyan") {
            $tColor = "[0;36m";
        } elseif ($uColor === "lightcyan") {
            $tColor = "[1;36m";
        } elseif ($uColor === "red") {
            $tColor = "[0;31m";
        } elseif ($uColor === "lightred") {
            $tColor = "[1;31m";
        } elseif ($uColor === "purple") {
            $tColor = "[0;35m";
        } elseif ($uColor === "lightpurple") {
            $tColor = "[1;35m";
        } elseif ($uColor === "brown") {
            $tColor = "[0;33m";
        } elseif ($uColor === "yellow") {
            $tColor = "[1;33m";
        } elseif ($uColor === "white") {
            $tColor = "[1;37m";
        } else /* if ($uColor === "lightgray") */ {
            $tColor = "[0;37m";
        }

        echo "\033{$tColor}{$uMessage}\033[0m\r\n";
    }

    /**
     * Writes given message
     *
     * @param string $uMessage message
     *
     * @return void
     */
    public function write($uMessage)
    {
        echo "{$uMessage}\r\n";
    }

    /**
     * Outputs the array to console
     *
     * @param array $uArray Target array will be printed
     *
     * @return void
     */
    public function writeArray(array $uArray)
    {
        print_r($uArray);
    }
}
