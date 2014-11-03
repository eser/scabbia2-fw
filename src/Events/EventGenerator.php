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

use Scabbia\Code\AnnotationManager;
use Scabbia\Code\TokenStream;
use Scabbia\Events\Events;
use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Generators\GeneratorRegistry;
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
    /** @type Events $events set of events */
    public $events;


    /**
     * Initializes a generator
     *
     * @param GeneratorRegistry  $uGeneratorRegistry   generator registry
     *
     * @return GeneratorBase
     */
    public function __construct(GeneratorRegistry $uGeneratorRegistry)
    {
        parent::__construct($uGeneratorRegistry);

        $this->events = new Events();
    }

    /**
     * Processes set of annotations
     *
     * @return void
     */
    public function processAnnotations()
    {
        foreach ($this->generatorRegistry->annotationManager->get("event", true) as $tScanResult) {
            if ($tScanResult[AnnotationManager::LEVEL] !== "staticMethods") {
                continue;
            }

            foreach ($tScanResult[AnnotationManager::VALUE] as $tEvent) {
                $this->events->register(
                    $tEvent["on"],
                    [$tScanResult[AnnotationManager::SOURCE], $tScanResult[AnnotationManager::MEMBER]],
                    null,
                    isset($tEvent["priority"]) ? $tEvent["priority"] : null
                );
            }
        }
    }

    /**
     * Finalizes generator process
     *
     * @return void
     */
    public function finalize()
    {
        $this->generatorRegistry->saveFile(
            "events.php",
            $this->events->events,
            true
        );
    }
}
