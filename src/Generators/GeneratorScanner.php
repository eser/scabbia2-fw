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

use Scabbia\Code\AnnotationScanner;
use Scabbia\Code\TokenStream;
use Scabbia\Config\Config;
use Scabbia\Framework\Core;
use Scabbia\Loaders\Loader;
use Scabbia\Helpers\FileSystem;
use Scabbia\Objects\CommandInterpreter;
use Scabbia\Tasks\TaskBase;
use RuntimeException;

/**
 * GeneratorScanner
 *
 * @package     Scabbia\Generators
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class GeneratorScanner
{
    /** @type array                   $generators         set of generators */
    public $generators = [];
    /** @type AnnotationScanner|null  $annotationScanner  annotation scanner */
    public $annotationScanner = null;


    /**
     * Executes the task
     *
     * @param array $uParameters parameters
     *
     * @throws RuntimeException if configuration is invalid
     * @return int exit code
     */
    public function executeTask(array $uParameters)
    {
        // initialize annotation scanner
        $this->annotationScanner = new AnnotationScanner();

        // initialize generators read from configuration
        if (isset($this->config["generators"])) {
            foreach ($this->config["generators"] as $tTaskGeneratorClass) {
                $tInstance = new $tTaskGeneratorClass (
                    $uApplicationConfig[$tApplicationKey],
                    $tApplicationWritablePath
                );

                foreach ($tInstance->annotations as $tAnnotationKey => $tAnnotation) {
                    $this->annotationScanner->annotations[$tAnnotationKey] = $tAnnotation;
                }

                $this->generators[$tTaskGeneratorClass] = $tInstance;
            }
        }

        // -- scan composer maps
        $tFolders = $this->scanComposerMaps();

        foreach ($this->generators as $tGenerator) {
            $tGenerator->initialize();
        }

        // -- process files
        foreach ($tFolders as $tPath) {
            if (file_exists($tPath[1])) {
                FileSystem::getFilesWalk(
                    $tPath[1],
                    "*.*",
                    true,
                    [$this, "processFile"],
                    $tPath[0]
                );
            }
        }

        foreach ($this->generators as $tGenerator) {
            $tGenerator->processAnnotations($this->annotationScanner->result);
        }

        foreach ($this->generators as $tGenerator) {
            $tGenerator->finalize();
        }

        foreach ($this->generators as $tGenerator) {
            $tGenerator->dump();
        }
    }

    /**
     * Scans the folders mapped in composer
     *
     * @return void
     */
    public function scanComposerMaps()
    {
        $tFolders = [];

        for ($tLevel = 0; $tLevel < Loader::LEVELS; $tLevel++) {
            // PSR-4 lookup
            foreach (Core::$loader->getPrefixesPsr4($tLevel) as $tPrefix => $tDirs) {
                foreach ($tDirs as $tDir) {
                    $tFolders[] = [$tPrefix, $tDir, "PSR-4"];
                }
            }

            // PSR-4 fallback dirs
            foreach (Core::$loader->getFallbackDirsPsr4($tLevel) as $tDir) {
                $tFolders[] = ["", $tDir, "PSR-4"];
            }

            // PSR-0 lookup
            foreach (Core::$loader->getPrefixesPsr0($tLevel) as $tPrefixes) {
                foreach ($tPrefixes as $tPrefix => $tDirs) {
                    foreach ($tDirs as $tDir) {
                        $tFolders[] = [$tPrefix, $tDir, "PSR-0"];
                    }
                }
            }

            // PSR-0 fallback dirs
            foreach (Core::$loader->getFallbackDirsPsr0($tLevel) as $tDir) {
                $tFolders[] = ["", $tDir, "PSR-0"];
            }
        }

        return $tFolders;
    }

    /**
     * Processes given file to search for classes
     *
     * @param string $uFile             file
     * @param string $uNamespacePrefix  namespace prefix
     *
     * @return void
     */
    public function processFile($uFile, $uNamespacePrefix)
    {
        $tFileContents = FileSystem::read($uFile);
        $tTokenStream = TokenStream::fromString($tFileContents);

        foreach ($this->generators as $tGenerator) {
            $tGenerator->processFile($uFile, $tFileContents, $tTokenStream);
        }

        if (substr($uFile, -4) !== ".php") {
            return;
        }

        $this->annotationScanner->processFile($tTokenStream, $uNamespacePrefix);
    }
}
