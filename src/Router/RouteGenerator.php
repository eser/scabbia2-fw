<?php
/**
 * Scabbia2 PHP Framework Code
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        http://github.com/scabbiafw/scabbia2-fw for the canonical source repository
 * @copyright   2010-2015 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Router;

use Scabbia\Code\AnnotationManager;
use Scabbia\Code\TokenStream;
use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Helpers\FileSystem;
use Scabbia\Router\Router;
use UnexpectedValueException;

/**
 * RouteGenerator
 *
 * @package     Scabbia\Router
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 *
 * @scabbia-generator
 *
 * Routing related code based on the nikic's FastRoute solution:
 * http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html
 */
class RouteGenerator extends GeneratorBase
{
    /** @type string FILTER_VALIDATE_BOOLEAN a symbolic constant for boolean validation */
    const APPROX_CHUNK_SIZE = 10;


    /** @type array $staticRoutes set of static routes */
    public $staticRoutes = [];
    /** @type array $regexToRoutesMap map of variable routes */
    public $regexToRoutesMap = [];
    /** @type array $namedRoutes map of named routes */
    public $namedRoutes = [];


    /**
     * Processes set of annotations
     *
     * @return void
     */
    public function processAnnotations()
    {
        foreach ($this->generatorRegistry->annotationManager->get("route", true) as $tScanResult) {
            if ($tScanResult[AnnotationManager::LEVEL] === "staticMethods") {
                $tCallback = $tScanResult[AnnotationManager::SOURCE] . "::" . $tScanResult[AnnotationManager::MEMBER];
            } elseif ($tScanResult[AnnotationManager::LEVEL] === "methods") {
                $tCallback = [$tScanResult[AnnotationManager::SOURCE], $tScanResult[AnnotationManager::MEMBER]];
            } else {
                continue;
            }

            foreach ($tScanResult[AnnotationManager::VALUE] as $tRoute) {
                foreach ($this->generatorRegistry->applicationConfig["modules"] as $tModuleKey => $tModuleDefinition) {
                    if (strncmp(
                        $tScanResult[AnnotationManager::SOURCE],
                        $tModuleDefinition["namespace"],
                        strlen($tModuleDefinition["namespace"])
                    ) !== 0) {
                        continue;
                    }

                    if ($tModuleKey === "front") {
                        $tModulePrefix = "";
                    } else {
                        $tModulePrefix = "/{$tModuleKey}";
                    }

                    $this->addRoute(
                        $tRoute["method"],
                        "{$tModulePrefix}{$tRoute["path"]}",
                        $tCallback,
                        isset($tRoute["name"]) ? $tRoute["name"] : null
                    );
                }
            }
        }
    }

    /**
     * Finalizes generator process
     *
     * @return void
     */
    public function finalize()
    {
        $this->generatorRegistry->saveFile(
            "routes.php",
            $this->getData(),
            true
        );
    }

    /**
     * Adds specified route
     *
     * @param string|array  $uMethods   http methods
     * @param string        $uRoute     route
     * @param callable      $uCallback  callback
     * @param string|null   $uName      name of route
     *
     * @return void
     */
    public function addRoute($uMethods, $uRoute, $uCallback, $uName = null)
    {
        $tRouteData = Router::parse($uRoute);
        $tMethods = (array)$uMethods;

        if (count($tRouteData) === 1 && is_string($tRouteData[0])) {
            $this->addStaticRoute($tMethods, $tRouteData, $uCallback, $uName);
        } else {
            $this->addVariableRoute($tMethods, $tRouteData, $uCallback, $uName);
        }
    }

    /**
     * Adds a static route
     *
     * @param array         $uMethods    http methods
     * @param array         $uRouteData  route data
     * @param callable      $uCallback   callback
     * @param string|null   $uName       name of route
     *
     * @throws UnexpectedValueException if an routing problem occurs
     * @return void
     */
    public function addStaticRoute(array $uMethods, $uRouteData, $uCallback, $uName = null)
    {
        $tRouteStr = $uRouteData[0];

        foreach ($uMethods as $tMethod) {
            if (isset($this->staticRoutes[$tRouteStr][$tMethod])) {
                throw new UnexpectedValueException(sprintf(
                    "Cannot register two routes matching \"%s\" for method \"%s\"",
                    $tRouteStr,
                    $tMethod
                ));
            }
        }

        foreach ($uMethods as $tMethod) {
            if (isset($this->regexToRoutesMap[$tMethod])) {
                foreach ($this->regexToRoutesMap[$tMethod] as $tRoute) {
                    if (preg_match("~^{$tRoute["regex"]}$~", $tRouteStr) === 1) {
                        throw new UnexpectedValueException(sprintf(
                            "Static route \"%s\" is shadowed by previously defined variable route \"%s\" for method \"%s\"",
                            $tRouteStr,
                            $tRoute["regex"],
                            $tMethod
                        ));
                    }
                }
            }

            $this->staticRoutes[$tRouteStr][$tMethod] = $uCallback;

            /*
            if ($uName !== null) {
                if (!isset($this->namedRoutes[$tMethod])) {
                    $this->namedRoutes[$tMethod] = [];
                }

                $this->namedRoutes[$tMethod][$uName] = [$tRouteStr, []];
            }
            */
            if ($uName !== null && !isset($this->namedRoutes[$uName])) {
                $this->namedRoutes[$uName] = [$tRouteStr, []];
            }
        }
    }

    /**
     * Adds a variable route
     *
     * @param array         $uMethods    http method
     * @param array         $uRouteData  route data
     * @param callable      $uCallback   callback
     * @param string|null   $uName       name of route
     *
     * @throws UnexpectedValueException if an routing problem occurs
     * @return void
     */
    public function addVariableRoute(array $uMethods, $uRouteData, $uCallback, $uName = null)
    {
        $tRegex = "";
        $tReverseRegex = "";
        $tVariables = [];

        foreach ($uRouteData as $tPart) {
            if (is_string($tPart)) {
                $tRegex .= preg_quote($tPart, "~");
                $tReverseRegex .= preg_quote($tPart, "~");
                continue;
            }

            list($tVariableName, $tRegexPart) = $tPart;

            if (isset($tVariables[$tVariableName])) {
                throw new UnexpectedValueException(sprintf("Cannot use the same placeholder \"%s\" twice", $tVariableName));
            }

            $tVariables[$tVariableName] = $tVariableName;
            $tRegex .= "({$tRegexPart})";
            $tReverseRegex .= "{{$tVariableName}}";
        }

        foreach ($uMethods as $tMethod) {
            if (isset($this->regexToRoutesMap[$tMethod][$tRegex])) {
                throw new UnexpectedValueException(
                    sprintf("Cannot register two routes matching \"%s\" for method \"%s\"", $tRegex, $tMethod)
                );
            }
        }

        foreach ($uMethods as $tMethod) {
            if (!isset($this->regexToRoutesMap[$tMethod])) {
                $this->regexToRoutesMap[$tMethod] = [];
            }

            $this->regexToRoutesMap[$tMethod][$tRegex] = [
                // "method"    => $tMethod,
                "callback"  => $uCallback,
                "regex"     => $tRegex,
                "variables" => $tVariables
            ];

            /*
            if ($uName !== null) {
                if (!isset($this->namedRoutes[$tMethod])) {
                    $this->namedRoutes[$tMethod] = [];
                }

                $this->namedRoutes[$tMethod][$uName] = [$tRegex, $tVariables];
            }
            */
            if ($uName !== null && !isset($this->namedRoutes[$uName])) {
                $this->namedRoutes[$uName] = [$tReverseRegex, array_values($tVariables)];
            }
        }
    }

    /**
     * Combines all route data in order to return it as a result of generation process
     *
     * @return array data
     */
    public function getData()
    {
        $tVariableRouteData = [];
        foreach ($this->regexToRoutesMap as $tMethod => $tRegexToRoutesMapOfMethod) {
            $tRegexToRoutesMapOfMethodCount = count($tRegexToRoutesMapOfMethod);

            $tNumParts = max(1, round($tRegexToRoutesMapOfMethodCount / self::APPROX_CHUNK_SIZE));
            $tChunkSize = ceil($tRegexToRoutesMapOfMethodCount / $tNumParts);

            $tChunks = array_chunk($tRegexToRoutesMapOfMethod, $tChunkSize, true);
            $tVariableRouteData[$tMethod] = array_map([$this, "processChunk"], $tChunks);
        }

        return [
            "static"   => $this->staticRoutes,
            "variable" => $tVariableRouteData,
            "named"    => $this->namedRoutes
        ];
    }

    /**
     * Splits variable routes into chunks
     *
     * @param array $uRegexToRoutesMap route definitions
     *
     * @return array chunked
     */
    protected function processChunk(array $uRegexToRoutesMap)
    {
        $tRouteMap = [];
        $tRegexes = [];
        $tNumGroups = 0;

        foreach ($uRegexToRoutesMap as $tRegex => $tRoute) {
            $tNumVariables = count($tRoute["variables"]);
            $tNumGroups = max($tNumGroups, $tNumVariables);

            $tRegexes[] = $tRegex . str_repeat("()", $tNumGroups - $tNumVariables);
            $tRouteMap[$tNumGroups + 1] = [$tRoute["callback"], $tRoute["variables"]];

            ++$tNumGroups;
        }

        return [
            "regex"    => "~^(?|" . implode("|", $tRegexes) . ")$~",
            "routeMap" => $tRouteMap
        ];
    }
}
