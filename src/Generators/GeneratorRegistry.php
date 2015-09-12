<?php
/**
 * Scabbia2 PHP Framework Code
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/scabbiafw/scabbia2-fw for the canonical source repository
 * @copyright   2010-2015 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Generators;

use Scabbia\Code\AnnotationManager;
use Scabbia\Framework\ApplicationBase;
use Scabbia\Framework\Core;
use Scabbia\Helpers\FileSystem;

/**
 * GeneratorRegistry
 *
 * @package     Scabbia\Generators
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 */
class GeneratorRegistry
{
    /** @type mixed                   $applicationConfig       application config */
    public $applicationConfig;
    /** @type string                  $applicationWritablePath application writable path */
    public $applicationWritablePath;
    /** @type array                   $generators              set of generators */
    public $generators = [];
    /** @type AnnotationManager|null  $annotationManager       annotation manager */
    public $annotationManager = null;


    /**
     * Initializes a generator registry
     *
     * @param mixed  $uApplicationConfig         application config
     * @param string $uApplicationWritablePath   application writable path
     *
     * @return GeneratorRegistry
     */
    public function __construct($uApplicationConfig, $uApplicationWritablePath)
    {
        $this->applicationConfig = $uApplicationConfig;
        $this->applicationWritablePath = $uApplicationWritablePath;
    }

    /**
     * Executes available generators
     *
     * @return void
     */
    public function execute()
    {
        $this->annotationManager = new AnnotationManager($this->applicationWritablePath);
        $this->annotationManager->load();

        foreach ($this->annotationManager->get("scabbia-generator") as $tScanResult) {
            if ($tScanResult[1] !== "class") {
                continue;
            }

            $this->generators[$tScanResult[0]] = new $tScanResult[0] ($this);
        }

        foreach ($this->generators as $tGenerator) {
            $tGenerator->processAnnotations();
        }

        // TODO processFile
        // $tGenerator->processFile($uPath, $uFileContents, TokenStream $uTokenStream);

        foreach ($this->generators as $tGenerator) {
            $tGenerator->finalize();
        }
    }

    /**
     * Saves a file into writable folder
     *
     * @param string $uFilename   filename
     * @param mixed  $uContent    file content to be written
     * @param bool   $uSaveAsPHP  saves file contents as php file if it is true
     *
     * @return void
     */
    public function saveFile($uFilename, $uContent, $uSaveAsPHP = false)
    {
        if ($uSaveAsPHP) {
            FileSystem::writePhpFile(
                Core::$instance->translateVariables($this->applicationWritablePath . "/{$uFilename}"),
                $uContent
            );

            return;
        }

        FileSystem::write(
            Core::$instance->translateVariables($this->applicationWritablePath . "/{$uFilename}"),
            $uContent
        );
    }
}
