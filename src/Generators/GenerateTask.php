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

namespace Scabbia\Generators;

use Scabbia\Tasks\TaskBase;
use Scabbia\Config\Config;
use Scabbia\Framework\Core;
use Scabbia\Helpers\FileSystem;
use Scabbia\Objects\CommandInterpreter;
use Scabbia\Yaml\Parser;
use ReflectionClass;
use RuntimeException;

/**
 * Task class for "php scabbia generate"
 *
 * @package     Scabbia\Generators
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 *
 * @todo only pass annotations requested by generator
 */
class GenerateTask extends TaskBase
{
    /** @type Parser|null $parser      yaml parser */
    public $parser = null;
    /** @type array       $generators  set of generators */
    public $generators = [];
    /** @type array       $annotations set of annotations */
    public $annotations = [];
    /** @type array       $result      result of generator task */
    public $result = null;


    /**
     * Registers the tasks itself to a command interpreter instance
     *
     * @param CommandInterpreter $uCommandInterpreter interpreter to be registered at
     *
     * @return void
     */
    public static function registerToCommandInterpreter(CommandInterpreter $uCommandInterpreter)
    {
        $uCommandInterpreter->addCommand(
            "generate",
            "Calls all generators registered to your project",
            [
                // type, name, description
                [Console::OPTION_FLAG, "--clean", ""]
            ]
        );
    }

    /**
     * Initializes the generate task
     *
     * @param mixed      $uConfig    configuration
     * @param IInterface $uInterface interface class
     *
     * @return GenerateTask
     */
    public function __construct($uConfig, $uInterface)
    {
        parent::__construct($uConfig, $uInterface);
    }

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

        $tProjectFile = FileSystem::combinePaths(Core::$basepath, Core::translateVariables($tProjectFile));
        $uApplicationConfig = Config::load($tProjectFile)->get();

        if (!isset($uApplicationConfig[$tApplicationKey])) {
            throw new RuntimeException("invalid configuration - {$tProjectFile}::{$tApplicationKey}");
        }

        // TODO is sanitizing $tApplicationKey needed for paths?
        $tApplicationWritablePath = Core::$basepath . "/writable/generated/app.{$tApplicationKey}";

        if (!file_exists($tApplicationWritablePath)) {
            mkdir($tApplicationWritablePath, 0777, true);
        }

        // initialize generators read from configuration
        if (isset($this->config["generators"])) {
            foreach ($this->config["generators"] as $tTaskGeneratorClass) {
                $tInstance = new $tTaskGeneratorClass (
                    $uApplicationConfig[$tApplicationKey],
                    $tApplicationWritablePath
                );

                foreach ($tInstance->annotations as $tAnnotationKey => $tAnnotation) {
                    $this->annotations[$tAnnotationKey] = $tAnnotation;
                }

                $this->generators[$tTaskGeneratorClass] = $tInstance;
            }
        }

        // -- scan composer maps
        Core::pushComposerPaths($uApplicationConfig[$tApplicationKey]);
        $tFolders = $this->scanComposerMaps();

        $this->interface->writeColor("green", "Composer Maps:");
        foreach ($tFolders as $tFolder) {
            $this->interface->writeColor("white", "- [{$tFolder[2]}] \\{$tFolder[0]} => {$tFolder[1]}");
        }

        // -- process files
        $this->result = [];
        foreach ($tFolders as $tPath) {
            FileSystem::getFilesWalk(
                $tPath[1],
                "*.*",
                true,
                [$this, "processFile"],
                $tPath[0]
            );
        }

        foreach ($this->generators as $tGenerator) {
            $tGenerator->initialize();
        }

        foreach ($this->generators as $tGenerator) {
            $tGenerator->processAnnotations($this->result);
        }

        foreach ($this->generators as $tGenerator) {
            $tGenerator->finalize();
        }

        Core::popComposerPaths();

        $this->interface->writeColor("yellow", "done.");

        return 0;
    }

    /**
     * Scans the folders mapped in composer
     *
     * @return void
     */
    public function scanComposerMaps()
    {
        $tFolders = [];

        // PSR-4 lookup
        foreach (Core::$composerAutoloader->getPrefixesPsr4() as $prefix => $dirs) {
            foreach ($dirs as $dir) {
                $tFolders[] = [$prefix, $dir, "PSR-4"];
            }
        }

        // PSR-4 fallback dirs
        foreach (Core::$composerAutoloader->getFallbackDirsPsr4() as $dir) {
            $tFolders[] = ["", $dir, "PSR-4"];
        }

        foreach (Core::$composerAutoloader->getPrefixes() as $dirs) {
            foreach ($dirs as $dir) {
                $tFolders[] = ["", $dir, "PSR-0"];
            }
        }

        // PSR-0 fallback dirs
        foreach (Core::$composerAutoloader->getFallbackDirs() as $dir) {
            $tFolders[] = ["", $dir, "PSR-0"];
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
        $tTokens = token_get_all($tFileContents);

        foreach ($this->generators as $tGenerator) {
            $tGenerator->processFile($uFile, $tFileContents, $tTokens);
        }

        if (substr($uFile, -4) !== ".php") {
            return;
        }

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
                    $this->processClass($tLastClass, $uNamespacePrefix);
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
    public function processClass($uClass, $uNamespacePrefix)
    {
        $tClassAnnotations = [
            // "class" => [],

            "methods" => [],
            "properties" => [],

            "staticMethods" => [],
            "staticProperties" => []
        ];
        $tCount = 0;

        $tReflection = new ReflectionClass($uClass);

        $tDocComment = $tReflection->getDocComment();
        if (strlen($tDocComment) > 0) {
            $tClassAnnotations["class"] = $this->parseAnnotations($tDocComment);
            $tCount++;
        } else {
            $tClassAnnotations["class"] = [];
        }

        // methods
        foreach ($tReflection->getMethods() as $tMethodReflection) {
            // TODO check the correctness of logic
            if ($tMethodReflection->class !== $uClass) {
                continue;
            }

            $tDocComment = $tMethodReflection->getDocComment();
            if (strlen($tDocComment) > 0) {
                $tParsedDocComment = $this->parseAnnotations($tDocComment);

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
            // TODO check the correctness of logic
            if ($tPropertyReflection->class !== $uClass) {
                continue;
            }

            $tDocComment = $tPropertyReflection->getDocComment();
            if (strlen($tDocComment) > 0) {
                $tParsedAnnotations = $this->parseAnnotations($tDocComment);

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
            $this->result[$uClass] = $tClassAnnotations;
        }
    }

    /**
     * Parses the docblock and returns annotations in an array
     *
     * @param string $uDocComment docblock which contains annotations
     *
     * @return array set of annotations
     */
    public function parseAnnotations($uDocComment)
    {
        preg_match_all(
            "/\\*[\\t| ]\\@([^\\n|\\t| ]+)(?:[\\t| ]([^\\n]+))*/",
            $uDocComment,
            $tDocCommentLines,
            PREG_SET_ORDER
        );

        $tParsedAnnotations = [];

        foreach ($tDocCommentLines as $tDocCommentLine) {
            if (!isset($this->annotations[$tDocCommentLine[1]])) {
                continue;
            }

            $tRegistryItem = $this->annotations[$tDocCommentLine[1]];

            if (!isset($tParsedAnnotations[$tDocCommentLine[1]])) {
                $tParsedAnnotations[$tDocCommentLine[1]] = [];
            }

            if (isset($tDocCommentLine[2])) {
                if ($tRegistryItem["format"] === "yaml") {
                    if ($this->parser === null) {
                        $this->parser = new Parser();
                    }

                    $tLine = $this->parser->parse($tDocCommentLine[2]);
                } else {
                    $tLine = $tDocCommentLine[2];
                }
            } else {
                $tLine = "";
            }

            $tParsedAnnotations[$tDocCommentLine[1]][] = $tLine;
        }

        return $tParsedAnnotations;
    }
}
