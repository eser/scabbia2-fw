<?php
/**
 * Scabbia2 PHP Framework Code
 * https://github.com/eserozvataf/scabbia2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/eserozvataf/scabbia2-fw for the canonical source repository
 * @copyright   2010-2016 Eser Ozvataf. (http://eser.ozvataf.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Code;

use Scabbia\Code\AnnotationManager;
use Scabbia\Code\Minifier;
use Scabbia\Code\TokenStream;
use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Generators\GeneratorRegistry;
use Scabbia\Helpers\FileSystem;
use Scabbia\Objects\Binder;
use RuntimeException;

/**
 * CompileGenerator
 *
 * @package     Scabbia\Code
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 *
 * @scabbia-generator
 */
class CompileGenerator extends GeneratorBase
{
    /** @type array $classes set of classes */
    public $classes = [];


    /**
     * Processes set of annotations
     *
     * @return void
     */
    public function processAnnotations()
    {
        foreach ($this->generatorRegistry->annotationManager->get("scabbia-compile") as $tScanResult) {
            if ($tScanResult[AnnotationManager::LEVEL] !== "class") {
                continue;
            }

            $this->classes[] = $tScanResult[AnnotationManager::SOURCE];
        }
    }

    /**
     * Finalizes generator process
     *
     * @return void
     */
    public function finalize()
    {
        $tMinifier = new Minifier();

        $tBinder = new Binder();
        $tBinder->addFilter(
            "application/x-httpd-php",
            function ($uInput) use ($tMinifier) {
                $tTokenStream = TokenStream::fromString($uInput);
                return $tMinifier->minifyPhpSource($tTokenStream);
            }
        );

        foreach ($this->classes as $tClass) {
            $tFilePath = Core::$instance->loader->findFile($tClass);
            if ($tFilePath === false) {
                // TODO exception
                throw new RuntimeException("");
            }

            // $tBinder->addContent("<" . "?php if (!class_exists(\"{$tClass}\", false)) { ?" . ">");
            $tBinder->addFile($tFilePath);
            // $tBinder->addContent("<" . "?php } ?" . ">");
        }

        $tContent = str_replace(" ?" . "><" . "?php ", " ", $tBinder->compile());

        $this->generatorRegistry->saveFile("compiled.php", $tContent);
    }
}
