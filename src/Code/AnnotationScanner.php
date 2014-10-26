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

use Scabbia\Code\TokenStream;
use ReflectionClass;

/**
 * AnnotationScanner
 *
 * @package     Scabbia\Code
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class AnnotationScanner
{
    /** @type array       $result      result of scanning task */
    public $result = [];
    /** @type array       $ignoreList  annotations to be ignored */
    public $ignoreList = [
        "link",
        "copyright",
        "license",
        "package",
        "author",
        "since",
        "type",
        "param",
        "return",
        "throws",
        "todo"
    ];


    /**
     * Scans a token stream and extracts annotations
     *
     * @param TokenStream $uTokenStream      extracted tokens wrapped with tokenstream
     * @param string      $uNamespacePrefix  namespace prefix
     *
     * @return array the file content in printable format with comments
     */
    public function process(TokenStream $uTokenStream, $uNamespacePrefix)
    {
        $tBuffer = "";

        $tUses = [];
        $tLastNamespace = null;
        $tLastClass = null;
        $tLastClassDerivedFrom = null;
        $tExpectation = 0; // 1=namespace, 2=class

        foreach ($uTokenStream as $tToken) {
            if ($tToken[0] === T_WHITESPACE) {
                continue;
            }

            if ($tExpectation === 0) {
                if ($tToken[0] === T_NAMESPACE) {
                    $tBuffer = "";
                    $tExpectation = 1;
                    continue;
                }

                if ($tToken[0] === T_CLASS) {
                    $tExpectation = 2;
                    continue;
                }

                if ($tToken[0] === T_USE) {
                    $tBuffer = "";
                    $tExpectation = 5;
                    continue;
                }
            } elseif ($tExpectation === 1) {
                if ($tToken[0] === T_STRING || $tToken[0] === T_NS_SEPARATOR) {
                    $tBuffer .= $tToken[1];
                } else {
                    $tLastNamespace = $tBuffer;
                    $tExpectation = 0;
                }
            } elseif ($tExpectation === 2) {
                $tLastClass = "{$tLastNamespace}\\{$tToken[1]}";
                $tExpectation = 3;
            } elseif ($tExpectation === 3) {
                if ($tToken[0] === T_EXTENDS) {
                    $tBuffer = "";
                    $tExpectation = 4;
                    continue;
                }

                $tSkip = false;
                if ($tLastClassDerivedFrom !== null && !class_exists($tLastClassDerivedFrom)) {
                    $tSkip = true;
                    // TODO throw exception instead.
                    echo sprintf(
                        "\"%s\" derived from \"%s\", but it could not be found.\n",
                        $tLastClass,
                        $tLastClassDerivedFrom
                    );
                }

                if (!$tSkip && !isset($this->result[$tLastClass])) {
                    $this->processClass($tLastClass, $uNamespacePrefix);
                }

                $tExpectation = 0;
            } elseif ($tExpectation === 4) {
                if ($tToken[0] === T_STRING || $tToken[0] === T_NS_SEPARATOR) {
                    $tBuffer .= $tToken[1];
                } else {
                    $tFound = false;

                    foreach ($tUses as $tUse) {
                        $tLength = strlen($tBuffer);
                        if (strlen($tUse) >= $tLength && substr($tUse, -$tLength) === $tBuffer) {
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
                if ($tToken[0] === T_STRING || $tToken[0] === T_NS_SEPARATOR) {
                    $tBuffer .= $tToken[1];
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
            "class" => [],

            "methods" => [],
            "properties" => [],

            "staticMethods" => [],
            "staticProperties" => []
        ];
        $tCount = 0;

        $tReflection = new ReflectionClass($uClass);

        $tDocComment = $tReflection->getDocComment();
        if (strlen($tDocComment) > 0) {
            $tClassAnnotations["class"]["self"] = $this->parseAnnotations($tDocComment);
            $tCount++;
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

        // if ($tCount > 0) {
        $this->result[$uClass] = $tClassAnnotations;
        // }
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
            if (in_array($tDocCommentLine[1], $this->ignoreList)) {
                continue;
            }

            if (!isset($tParsedAnnotations[$tDocCommentLine[1]])) {
                $tParsedAnnotations[$tDocCommentLine[1]] = [];
            }

            if (isset($tDocCommentLine[2])) {
                $tParsedAnnotations[$tDocCommentLine[1]][] = trim($tDocCommentLine[2]);
            }
        }

        return $tParsedAnnotations;
    }
}
