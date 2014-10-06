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

namespace Scabbia\Interfaces;

/**
 * Default methods needed for implementation of output in various interfaces
 *
 * @package     Scabbia\Interfaces
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
interface IInterface
{
    /**
     * Writes given message in header format
     *
     * @param int    $uHeading size
     * @param string $uMessage message
     *
     * @return void
     */
    public function writeHeader($uHeading, $uMessage);

    /**
     * Writes given message in specified color
     *
     * @param string $uColor   color
     * @param string $uMessage message
     *
     * @return void
     */
    public function writeColor($uColor, $uMessage);

    /**
     * Writes given message
     *
     * @param string $uMessage message
     *
     * @return void
     */
    public function write($uMessage);

    /**
     * Outputs the array in specified representation
     *
     * @param array $uArray Target array will be printed
     *
     * @return void
     */
    public function writeArray(array $uArray);
}
