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

namespace Scabbia\Code;

use Scabbia\Code\Minifier;
use Scabbia\Code\TokenStream;
use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Helpers\FileSystem;
use Scabbia\Objects\Binder;
use RuntimeException;

/**
 * CompileGenerator
 *
 * @package     Scabbia\Code
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 *
 * @scabbia-generator
 */
class CompileGenerator extends GeneratorBase
{
    /** @type array $annotations set of annotations */
    public $annotations = [
        "scabbia-compile" => ["format" => "yaml"]
    ];
    /** @type array $classes set of classes */
    public $classes;


    /**
     * Initializes generator
     *
     * @return void
     */
    public function initialize()
    {
        $this->classes = [];
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
        foreach ($uAnnotations as $tClass => $tAnnotation) {
            if (isset($tAnnotation["class"]["scabbia-compile"])) {
                $this->classes[] = $tClass;
            }
        }
    }

    /**
     * Dumps generated data into file
     *
     * @throws RuntimeException if class could not be loaded
     * @return void
     */
    public function dump()
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
            $tFilePath = Core::$loader->findFile($tClass);
            if ($tFilePath === false) {
                // TODO exception
                throw new RuntimeException("");
            }

            // $tBinder->addContent("<" . "?php if (!class_exists(\"{$tClass}\", false)) { ?" . ">");
            $tBinder->addFile($tFilePath);
            // $tBinder->addContent("<" . "?php } ?" . ">");
        }

        $tContent = str_replace(" ?" . "><" . "?php ", " ", $tBinder->compile());

        FileSystem::write(
            Core::translateVariables($this->outputPath . "/compiled.php"),
            $tContent
        );
    }
}
