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

namespace Scabbia\Documentor;

use Scabbia\Code\TokenStream;
use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Helpers\FileSystem;

/**
 * DocumentGenerator
 *
 * @package     Scabbia\Documentor
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 *
 * @scabbia-generator
 */
class DocumentGenerator extends GeneratorBase
{
    /** @type array $files set of files */
    public $files = [];


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
        $tRelativePath = substr($uPath, strlen(Core::$instance->loader->basepath) + 1);
        $tDocTitle = $tRelativePath;

        foreach ($uTokenStream as $tToken) {
            if ($tToken[0] === T_COMMENT) {
                if (strncmp($tToken[1], "// MD-TITLE ", 12) === 0) {
                    $tDocTitle = substr($tToken[1], 12);
                    continue;
                }

                if (strncmp($tToken[1], "// MD ", 6) === 0) {
                    $tDocLines[] = substr($tToken[1], 6);
                    continue;
                }
            }
        }

        if (count($tDocLines) > 0) {
            $this->files[$tRelativePath] = [$tDocTitle, $tDocLines];
        }
    }

    /**
     * Finalizes generator process
     *
     * @return void
     */
    public function finalize()
    {
        $tContent = "";
        foreach ($this->files as $tFileKey => $tFileContent) {
            $tContent .= "# {$tFileContent[0]}\n";

            foreach ($tFileContent[1] as $tLine) {
                $tContent .= $tLine;
            }

            $tContent .= "\n\n";
        }

        $this->generatorRegistry->saveFile("documentor.md", $tContent);
    }
}
