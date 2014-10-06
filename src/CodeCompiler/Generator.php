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

namespace Scabbia\CodeCompiler;

use Scabbia\CodeCompiler\Minifier;
use Scabbia\CodeCompiler\TokenStream;
use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Helpers\FileSystem;
use Scabbia\Objects\Binder;
use Exception;

/**
 * Generator
 *
 * @package     Scabbia\CodeCompiler
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 *
 * @scabbia-generator
 */
class Generator extends GeneratorBase
{
    /** @type array $annotations set of annotations */
    public $annotations = [];
    /** @type array $files set of files */
    public $files = [];


    /**
     * Initializes a generator
     *
     * @param mixed  $uApplicationConfig application config
     * @param string $uOutputPath        output path
     *
     * @return Generator
     */
    public function __construct($uApplicationConfig, $uOutputPath)
    {
        parent::__construct($uApplicationConfig, $uOutputPath);
    }

    /**
     * Initializes generator
     *
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * Processes a file
     *
     * @param string      $uPath         file path
     * @param string      $uFileContents contents of file
     * @param TokenStream $uTokenStream  extracted tokens wrapped with tokenstream
     *
     * @return void
     */
    public function processFile($uPath, $uFileContents, TokenStream $uTokenStream)
    {
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
    }

    /**
     * Finalizes generator
     *
     * @throws Exception if one of the files in the namespace list does not exist
     * @return void
     */
    public function finalize()
    {
        // TODO read from configuration
        $tFileNamespaceList = [
            "Scabbia\\Helpers\\Arrays.php",
            "Scabbia\\Helpers\\Date.php",
            // "Scabbia\\Helpers\\FileSystem.php",
            "Scabbia\\Helpers\\Html.php",
            "Scabbia\\Helpers\\Runtime.php",
            "Scabbia\\Helpers\\String.php"
        ];

        $tMinifier = new Minifier();

        $tBinder = new Binder();
        $tBinder->addFilter(
            "application/x-httpd-php",
            function ($uInput) use ($tMinifier) {
                $tTokenStream = TokenStream::fromString($uInput);
                return $tMinifier->minifyPhpSource($tTokenStream);
            }
        );

        foreach ($tFileNamespaceList as $tFileNamespace) {
            $tFilePath = Core::findResource($tFileNamespace);
            if ($tFilePath === false) {
                // TODO exception
                throw new Exception("");
            }

            // TODO add checking class_exists for php files
            $tBinder->addFile($tFilePath);
        }

        FileSystem::write(
            Core::translateVariables($this->outputPath . "/compiled.php"),
            $tBinder->compile()
        );
    }
}
