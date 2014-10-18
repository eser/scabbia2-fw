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

namespace Scabbia\Events;

use Scabbia\Code\TokenStream;
use Scabbia\Events\Events;
use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Helpers\FileSystem;

/**
 * EventGenerator
 *
 * @package     Scabbia\Events
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 *
 * @scabbia-generator
 */
class EventGenerator extends GeneratorBase
{
    /** @type array $annotations set of annotations */
    public $annotations = [
        "event" => ["format" => "yaml"]
    ];
    /** @type Events $events set of events */
    public $events;


    /**
     * Initializes generator
     *
     * @return void
     */
    public function initialize()
    {
        $this->events = new Events();
    }

    /**
     * Processes set of annotations
     *
     * @param array $uAnnotations annotations
     *
     * @return void
     */
    public function processAnnotations($uAnnotations)
    {
        foreach ($uAnnotations as $tClassKey => $tClass) {
            foreach ($tClass["staticMethods"] as $tMethodKey => $tMethod) {
                if (!isset($tMethod["event"])) {
                    continue;
                }

                foreach ($tMethod["event"] as $tEvent) {
                    $this->events->register(
                        $tEvent["on"],
                        [$tClassKey, $tMethodKey],
                        null,
                        isset($tEvent["priority"]) ? $tEvent["priority"] : null
                    );
                }
            }
        }
    }

    /**
     * Dumps generated data into file
     *
     * @return void
     */
    public function dump()
    {
        FileSystem::writePhpFile(
            Core::translateVariables($this->outputPath . "/events.php"),
            $this->events->events
        );
    }
}
