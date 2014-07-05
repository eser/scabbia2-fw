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

namespace Scabbia\Documentor;

use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Helpers\FileSystem;

/**
 * Generator
 *
 * @package     Scabbia\Documentor
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
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
     * Processes a file
     *
     * @param string $uPath         file path
     * @param string $uFileContents contents of file
     * @param string $uTokens       tokens extracted by tokenizer
     *
     * @return void
     */
    public function processFile($uPath, $uFileContents, $uTokens)
    {
        $tDocLines = [];

        foreach ($uTokens as $tToken) {
            if (is_array($tToken)) {
                $tTokenId = $tToken[0];
                $tTokenContent = $tToken[1];
            } else {
                $tTokenId = null;
                $tTokenContent = $tToken;
            }

            if ($tTokenId === T_COMMENT) {
                if (strncmp($tTokenContent, "// MD ", 6) === 0) {
                    $tDocLines[] = substr($tTokenContent, 6);
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
            $tContent .= "# {$tFileKey}" . PHP_EOL;

            foreach ($tFileContent as $tLine) {
                $tContent .= $tLine;
            }

            $tContent .= PHP_EOL . PHP_EOL;
        }

        FileSystem::write(
            Core::translateVariables($this->outputPath . "/documentor.md"),
            $tContent
        );
    }
}
