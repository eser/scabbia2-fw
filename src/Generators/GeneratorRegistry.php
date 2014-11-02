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

namespace Scabbia\Generators;

use Scabbia\Code\AnnotationManager;
use Scabbia\Framework\ApplicationBase;
use Scabbia\Helpers\FileSystem;

/**
 * GeneratorRegistry
 *
 * @package     Scabbia\Generators
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class GeneratorRegistry
{
    /** @type ApplicationBase         $application        application */
    public $application;
    /** @type array                   $generators         set of generators */
    public $generators = [];
    /** @type array|null              $outputs            set of generator outputs */
    public $outputs = null;
    /** @type AnnotationManager|null  $annotationManager  annotation manager */
    public $annotationManager = null;


    /**
     * Initializes a generator registry
     *
     * @param ApplicationBase  $uApplication   application
     *
     * @return GeneratorRegistry
     */
    public function __construct(ApplicationBase $uApplication)
    {
        $this->application = $uApplication;
    }

    /**
     * Loads saved generator outputs or start over executing them
     * 
     * @return void
     */
    public function load()
    {
        $tGeneratorOutputsPath = $this->application->writablePath . "/generator-outputs.php";

        // TODO and not in development mode
        if (file_exists($tGeneratorOutputsPath)) {
            $this->annotationMap = require $tGeneratorOutputsPath;
        } else {
            $this->execute();
            // TODO if not in readonly mode
            FileSystem::writePhpFile($tGeneratorOutputsPath, $this->outputs);
        }
    }

    /**
     * Executes available generators
     *
     * @return void
     */
    public function execute()
    {
        $this->annotationManager = new AnnotationManager($this->application);
        $this->annotationManager->load();

        foreach ($this->annotationManager->get("scabbia-generator") as $tScanResult) {
            if ($tScanResult[1] !== "class") {
                continue;
            }

            $this->generators[$tScanResult[0]] = new $tScanResult[0] ($this->application);
        }

        foreach ($this->generators as $tGenerator) {
            // TODO processAnnotations (check $tGenerator->annotations)
            // $tGenerator->processAnnotations($uAnnotations);
        }

        // TODO processFile
        // $tGenerator->processFile($uPath, $uFileContents, TokenStream $uTokenStream);

        foreach ($this->generators as $tGeneratorKey => $tGenerator) {
            $this->outputs[$tGeneratorKey] = $tGenerator->dump();
        }
    }
}
