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

namespace Scabbia\Framework\Commands;

use Scabbia\Framework\Core;
use Scabbia\Framework\Io;
use Scabbia\Config\Config;
use Scabbia\Yaml\Parser;
use Scabbia\Output\IOutput;

/**
 * Command class for "php scabbia generate"
 *
 * @package     Scabbia\Framework\Commands
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class GenerateCommand
{
    /** @type Parser|null $parser yaml parser */
    public static $parser = null;
    /** @type array $config configuration of generator command */
    public static $config = null;
    /** @type array $annotations result of generator command */
    public static $result = null;


    /**
     * Entry point for the command
     *
     * @param array   $uParameters command parameters
     * @param mixed   $uConfig     command configuration
     * @param IOutput $uOutput     output
     *
     * @throws \RuntimeException if configuration is invalid
     * @return int exit code
     */
    public static function generate(array $uParameters, $uConfig, IOutput $uOutput)
    {
        self::$config = $uConfig;

        if (count($uParameters) === 0) {
            $tProjectFile = "project.yml";
            $tApplicationKey = "default";
        } else {
            $tExploded = explode("/", $uParameters[0], 2);
            if (count($tExploded) === 1) {
                $tProjectFile = "project.yml";
                $tApplicationKey = $tExploded[0];
            } else {
                $tProjectFile = $tExploded[0];
                $tApplicationKey = $tExploded[1];
            }
        }

        $tProjectFile = Io::combinePaths(Core::$basepath, $tProjectFile);
        $uApplicationConfig = Config::load($tProjectFile)->get();

        if (!isset($uApplicationConfig[$tApplicationKey])) {
            throw new \RuntimeException("invalid configuration - {$tProjectFile}::{$tApplicationKey}");
        }

        // TODO: is sanitizing $tApplicationKey needed for paths?
        $tApplicationWritablePath = Core::$basepath . "/writable/generated/app.{$tApplicationKey}";

        if (!file_exists($tApplicationWritablePath)) {
            mkdir($tApplicationWritablePath, 0777, true);
        }

        // -- scan composer maps
        Core::pushComposerPaths($uApplicationConfig[$tApplicationKey]);
        $tFolders = self::scanComposerMaps($uOutput);

        $uOutput->writeColor("green", "Composer Maps:");
        foreach ($tFolders as $tFolder) {
            $uOutput->writeColor("white", "- {$tFolder[0]} => {$tFolder[1]}");
        }

        // -- process files
        self::$result = [];
        foreach ($tFolders as $tPath) {
            Io::getFilesWalk(
                $tPath[1],
                "*.php",
                true,
                [__CLASS__, "processFile"],
                $tPath[0]
            );
        }

        if (isset(self::$config["methods"])) {
            $tCommandMethods = self::$config["methods"];

            foreach ($tCommandMethods as $tCommandMethod) {
                call_user_func($tCommandMethod, self::$result, $tApplicationWritablePath);
            }
        }

        Core::popComposerPaths();
        $uOutput->writeColor("yellow", "done.");

        return 0;
    }

    /**
     * Scans the folders mapped in composer
     *
     * @return void
     */
    public static function scanComposerMaps()
    {
        $tFolders = [];

        // PSR-4 lookup
        foreach (Core::$composerAutoloader->getPrefixesPsr4() as $prefix => $dirs) {
            foreach ($dirs as $dir) {
                $tFolders[] = [$prefix, $dir];
            }
        }

        // PSR-4 fallback dirs
        foreach (Core::$composerAutoloader->getFallbackDirsPsr4() as $dir) {
            $tFolders[] = ["", $dir];
        }

        foreach (Core::$composerAutoloader->getPrefixes() as $dirs) {
            foreach ($dirs as $dir) {
                $tFolders[] = ["", $dir];
            }
        }

        // PSR-0 fallback dirs
        foreach (Core::$composerAutoloader->getFallbackDirs() as $dir) {
            $tFolders[] = ["", $dir];
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
    public static function processFile($uFile, $uNamespacePrefix)
    {
        $tFileContents = Io::read($uFile);
        $tTokens = token_get_all($tFileContents);

        $tBuffer = "";

        $tUses = [];
        $tLastNamespace = null;
        $tLastClass = null;
        $tLastClassDerivedFrom = null;
        $tExpectation = 0; // 1=namespace, 2=class

        foreach ($tTokens as $tToken) {
            if (is_array($tToken)) {
                $tTokenId = $tToken[0];
                $tTokenContent = $tToken[1];
            } else {
                $tTokenId = null;
                $tTokenContent = $tToken;
            }

            if ($tTokenId === T_WHITESPACE) {
                continue;
            }

            if ($tExpectation === 0) {
                if ($tTokenId === T_NAMESPACE) {
                    $tBuffer = "";
                    $tExpectation = 1;
                    continue;
                }

                if ($tTokenId === T_CLASS) {
                    $tExpectation = 2;
                    continue;
                }

                if ($tTokenId === T_USE) {
                    $tBuffer = "";
                    $tExpectation = 5;
                    continue;
                }
            } elseif ($tExpectation === 1) {
                if ($tTokenId === T_STRING || $tTokenId === T_NS_SEPARATOR) {
                    $tBuffer .= $tTokenContent;
                } else {
                    $tLastNamespace = $tBuffer;
                    $tExpectation = 0;
                }
            } elseif ($tExpectation === 2) {
                $tLastClass = "{$tLastNamespace}\\{$tTokenContent}";
                $tExpectation = 3;
            } elseif ($tExpectation === 3) {
                if ($tTokenId === T_EXTENDS) {
                    $tBuffer = "";
                    $tExpectation = 4;
                    continue;
                }

                $tSkip = false;
                if ($tLastClassDerivedFrom !== null && !class_exists($tLastClassDerivedFrom)) {
                    $tSkip = true;
                    // TODO throw exception instead.
                    echo "\"{$tLastClass}\" derived from \"{$tLastClassDerivedFrom}\", but it could not be found.\n";
                }

                if (!$tSkip) {
                    self::processClass($tLastClass, $uNamespacePrefix);
                }

                $tExpectation = 0;
            } elseif ($tExpectation === 4) {
                if ($tTokenId === T_STRING || $tTokenId === T_NS_SEPARATOR) {
                    $tBuffer .= $tTokenContent;
                } else {
                    $tFound = false;

                    foreach ($tUses as $tUse) {
                        $tLength = strlen($tBuffer);
                        if (strlen($tUse) > $tLength && substr($tUse, -$tLength) === $tBuffer) {
                            $tLastClassDerivedFrom = $tUse;
                            $tFound = true;
                            break;
                        }
                    }

                    if (!$tFound) {
                        if (strpos($tBuffer, "\\") !== false) {
                            $tLastClassDerivedFrom = $tBuffer;
                        } else {
                            $tLastClassDerivedFrom = "{$tLastNamespace}\\{$tBuffer}";
                        }
                    }

                    $tExpectation = 3;
                }
            } elseif ($tExpectation === 5) {
                if ($tTokenId === T_STRING || $tTokenId === T_NS_SEPARATOR) {
                    $tBuffer .= $tTokenContent;
                } else {
                    $tUses[] = $tBuffer;
                    $tExpectation = 0;
                }
            }
        }
    }

    /**
     * Processes classes using reflection to scan annotations
     *
     * @param string $uClass            class name
     * @param string $uNamespacePrefix  namespace prefix
     *
     * @return void
     */
    public static function processClass($uClass, $uNamespacePrefix)
    {
        $tClassAnnotations = [
            // "class" => [],

            "methods" => [],
            "properties" => [],

            "staticMethods" => [],
            "staticProperties" => []
        ];
        $tCount = 0;

        $tReflection = new \ReflectionClass($uClass);

        $tDocComment = $tReflection->getDocComment();
        if (strlen($tDocComment) > 0) {
            $tClassAnnotations["class"] = self::parseAnnotations($tDocComment);
            $tCount++;
        }

        // methods
        foreach ($tReflection->getMethods() as $tMethodReflection) {
            // TODO: check the correctness of logic
            if ($tMethodReflection->class !== $uClass) {
                continue;
            }

            $tDocComment = $tMethodReflection->getDocComment();
            if (strlen($tDocComment) > 0) {
                $tParsedDocComment = self::parseAnnotations($tDocComment);

                if (count($tParsedDocComment) === 0) {
                    // nothing
                } elseif ($tMethodReflection->isStatic()) {
                    $tClassAnnotations["staticMethods"][$tMethodReflection->name] = $tParsedDocComment;
                    $tCount++;
                } else {
                    $tClassAnnotations["methods"][$tMethodReflection->name] = $tParsedDocComment;
                    $tCount++;
                }
            }
        }

        // properties
        foreach ($tReflection->getProperties() as $tPropertyReflection) {
            // TODO: check the correctness of logic
            if ($tPropertyReflection->class !== $uClass) {
                continue;
            }

            $tDocComment = $tPropertyReflection->getDocComment();
            if (strlen($tDocComment) > 0) {
                $tParsedAnnotations = self::parseAnnotations($tDocComment);

                if (count($tParsedAnnotations) === 0) {
                    // nothing
                } elseif ($tPropertyReflection->isStatic()) {
                    $tClassAnnotations["staticProperties"][$tPropertyReflection->name] = $tParsedAnnotations;
                    $tCount++;
                } else {
                    $tClassAnnotations["properties"][$tPropertyReflection->name] = $tParsedAnnotations;
                    $tCount++;
                }
            }
        }

        if ($tCount > 0) {
            self::$result[$uClass] = $tClassAnnotations;
        }
    }

    /**
     * Parses the docblock and returns annotations in an array
     *
     * @param string $uDocComment docblock which contains annotations
     *
     * @return array set of annotations
     */
    public static function parseAnnotations($uDocComment)
    {
        preg_match_all(
            "/\\*[\\t| ]\\@([^\\n|\\t| ]+)(?:[\\t| ]([^\\n]+))*/",
            $uDocComment,
            $tDocCommentLines,
            PREG_SET_ORDER
        );

        $tParsedAnnotations = [];

        if (isset(self::$config["annotations"])) {
            $tAnnotationDefinitions = self::$config["annotations"];

            foreach ($tDocCommentLines as $tDocCommentLine) {
                if (!isset($tAnnotationDefinitions[$tDocCommentLine[1]])) {
                    continue;
                }

                $tRegistryItem = $tAnnotationDefinitions[$tDocCommentLine[1]];

                if (!isset($tParsedAnnotations[$tDocCommentLine[1]])) {
                    $tParsedAnnotations[$tDocCommentLine[1]] = [];
                }

                if (isset($tDocCommentLine[2])) {
                    if ($tRegistryItem["format"] === "yaml") {
                        if (self::$parser === null) {
                            self::$parser = new Parser();
                        }

                        $tLine = self::$parser->parse($tDocCommentLine[2]);
                    } else {
                        $tLine = $tDocCommentLine[2];
                    }
                } else {
                    $tLine = "";
                }

                $tParsedAnnotations[$tDocCommentLine[1]][] = $tLine;
            }
        }

        return $tParsedAnnotations;
    }
}
