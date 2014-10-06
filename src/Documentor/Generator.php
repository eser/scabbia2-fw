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

namespace Scabbia\Documentor;

use Scabbia\CodeCompiler\TokenStream;
use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Helpers\FileSystem;

/**
 * Generator
 *
 * @package     Scabbia\Documentor
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
        $tDocLines = [];

        foreach ($uTokenStream as $tToken) {
            if ($tToken[0] === T_COMMENT) {
                if (strncmp($tToken[1], "// MD ", 6) === 0) {
                    $tDocLines[] = substr($tToken[1], 6);
                }
            }
        }

        if (count($tDocLines) > 0) {
            $this->files[$uPath] = $tDocLines;
        }
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
     * @return void
     */
    public function finalize()
    {
        $tContent = "";
        foreach ($this->files as $tFileKey => $tFileContent) {
            $tContent .= "# {$tFileKey}\n";

            foreach ($tFileContent as $tLine) {
                $tContent .= $tLine;
            }

            $tContent .= "\n\n";
        }

        FileSystem::write(
            Core::translateVariables($this->outputPath . "/documentor.md"),
            $tContent
        );
    }
}
