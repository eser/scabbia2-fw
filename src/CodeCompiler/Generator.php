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

use Scabbia\CodeCompiler\TokenStream;
use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Helpers\FileSystem;
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
     * @param string $uPath         file path
     * @param string $uFileContents contents of file
     * @param string $uTokenStream  extracted tokens wrapped with tokenstream
     *
     * @return void
     */
    public function processFile($uPath, $uFileContents, $uTokenStream)
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

        $tCompilationContent = "";
        foreach ($tFileNamespaceList as $tFileNamespace) {
            $tFilePath = Core::findResource($tFileNamespace);
            if ($tFilePath === false) {
                // TODO exception
                throw new Exception("");
            }

            // TODO add checking class_exists for php files
            $tCompilationContent .= $this->minifyPhpSource(FileSystem::read($tFilePath));
        }

        FileSystem::write(
            Core::translateVariables($this->outputPath . "/compiled.php"),
            $tCompilationContent
        );
    }

    /**
     * Returns a minified php source
     *
     * @param string    $uInput         php source code
     *
     * @return array the file content in printable format with comments
     */
    public function minifyPhpSource($uInput)
    {
        $tReturn = "";
        $tLastToken = -1;
        $tOpenStack = [];

        $tTokenStream = new TokenStream(token_get_all($uInput));
        foreach ($tTokenStream as $tToken) {
            // $tReturn .= PHP_EOL . token_name($tToken[0]) . PHP_EOL;
            if ($tToken[0] === T_OPEN_TAG) {
                $tReturn .= "<" . "?php ";
                $tOpenStack[] = $tToken[0];
            } elseif ($tToken[0] === T_OPEN_TAG_WITH_ECHO) {
                $tReturn .= "<" . "?php echo ";
                $tOpenStack[] = $tToken[0];
            } elseif ($tToken[0] === T_CLOSE_TAG) {
                $tLastOpen = array_pop($tOpenStack);

                if ($tLastOpen === T_OPEN_TAG_WITH_ECHO) {
                    $tReturn .= "; ";
                } else {
                    if ($tLastToken !== T_WHITESPACE) {
                        $tReturn .= " ";
                    }
                }

                $tReturn .= "?" . ">";
            } elseif ($tToken[0] === T_COMMENT || $tToken[0] === T_DOC_COMMENT) {
                // skip comments
            } elseif ($tToken[0] === T_WHITESPACE) {
                if ($tLastToken !== T_WHITESPACE &&
                    $tLastToken !== T_OPEN_TAG &&
                    $tLastToken !== T_OPEN_TAG_WITH_ECHO &&
                    $tLastToken !== T_COMMENT &&
                    $tLastToken !== T_DOC_COMMENT
                ) {
                    $tReturn .= " ";
                }
            } elseif ($tToken[0] === null) {
                $tReturn .= $tToken[1];
                if ($tLastToken === T_END_HEREDOC) {
                    $tReturn .= "\n";
                    $tToken[0] = T_WHITESPACE;
                }
            } else {
                $tReturn .= $tToken[1];
            }

            $tLastToken = $tToken[0];
        }

        while (count($tOpenStack) > 0) {
            $tLastOpen = array_pop($tOpenStack);
            if ($tLastOpen === T_OPEN_TAG_WITH_ECHO) {
                $tReturn .= "; ";
            } else {
                if ($tLastToken !== T_WHITESPACE) {
                    $tReturn .= " ";
                }
            }

            $tReturn .= "?" . ">";
        }

        return $tReturn;
    }
}
