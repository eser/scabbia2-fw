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
use Scabbia\Framework\Io;
use Scabbia\Events\Events;

/**
 * Generator
 *
 * @package     Scabbia\Events
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class Generator
{
    /**
     * Entry point for processor
     *
     * @param array  $uAnnotations  annotations
     * @param string $uWritablePath writable output folder
     *
     * @return void
     */
    public static function generate(array $uAnnotations, $uWritablePath)
    {
        $tEvents = new Events();

        foreach ($uAnnotations as $tClassKey => $tClass) {
            foreach ($tClass["staticMethods"] as $tMethodKey => $tMethod) {
                if (!isset($tMethod["event"])) {
                    continue;
                }

                foreach ($tMethod["event"] as $tEvent) {
                    $tEvents->register(
                        $tEvent["on"],
                        [$tClassKey, $tMethodKey],
                        null,
                        isset($tEvent["priority"]) ? $tEvent["priority"] : null
                    );
                }
            }
        }

        Io::writePhpFile("{$uWritablePath}/events.php", $tEvents->events);
    }
}
