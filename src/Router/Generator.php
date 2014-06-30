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

namespace Scabbia\Router;

use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Helpers\FileSystem;
use Scabbia\Router\Router;

/**
 * Generator
 *
 * @package     Scabbia\Router
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 *
 * Routing related code based on the nikic's FastRoute solution:
 * http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html
 */
class Generator extends GeneratorBase
{
    /** @type string FILTER_VALIDATE_BOOLEAN a symbolic constant for boolean validation */
    const APPROX_CHUNK_SIZE = 10;


    /** @type array $annotations set of annotations */
    public $annotations = [
        "route" => ["format" => "yaml"]
    ];
    /** @type array $staticRoutes set of static routes */
    public $staticRoutes = [];
    /** @type array $regexToRoutesMap map of variable routes */
    public $regexToRoutesMap = [];
    /** @type array $namedRoutes map of named routes */
    public $namedRoutes = [];


    /**
     * Initializes a generator
     *
     * @return Generator
     */
    public function __construct()
    {
        parent::__construct();
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
        foreach ($uAnnotations as $tClassKey => $tClass) {
            foreach ($tClass["methods"] as $tMethodKey => $tMethod) {
                if (!isset($tMethod["route"])) {
                    continue;
                }

                foreach ($tMethod["route"] as $tRoute) {
                    $this->addRoute(
                        $tRoute["method"],
                        $tRoute["path"],
                        [$tClassKey, $tMethodKey],
                        isset($tRoute["name"]) ? $tRoute["name"] : null
                    );
                }
            }
        }

        FileSystem::writePhpFile(Core::translateVariables($this->outputPath . "/routes.php"), $this->getData());
    }

    /**
     * Finalizes generator
     *
     * @return void
     */
    public function finalize()
    {
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
     * @throws \Exception if an routing problem occurs
     * @return void
     */
    public function addStaticRoute(array $uMethods, $uRouteData, $uCallback, $uName = null)
    {
        $tRouteStr = $uRouteData[0];

        foreach ($uMethods as $tMethod) {
            if (isset($this->staticRoutes[$tRouteStr][$tMethod])) {
                throw new \Exception(
                    "Cannot register two routes matching \"{$tRouteStr}\" for method \"{$tMethod}\""
                );
            }
        }

        foreach ($uMethods as $tMethod) {
            foreach ($this->regexToRoutesMap as $tRoutes) {
                if (!isset($tRoutes[$tMethod])) {
                    continue;
                }

                $tRoute = $tRoutes[$tMethod];
                if (preg_match("~^{$tRoute["regex"]}$~", $tRouteStr) === 1) {
                    throw new \Exception(
                        "Static route \"{$tRouteStr}\" is shadowed by previously defined variable route
                        \"{$tRoute["regex"]}\" for method \"{$tMethod}\""
                    );
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
     * @throws \Exception if an routing problem occurs
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
                throw new \Exception("Cannot use the same placeholder \"{$tVariableName}\" twice");
            }

            $tVariables[$tVariableName] = $tVariableName;
            $tRegex .= "({$tRegexPart})";
            $tReverseRegex .= "{{$tVariableName}}";
        }

        foreach ($uMethods as $tMethod) {
            if (isset($this->regexToRoutesMap[$tRegex][$tMethod])) {
                throw new \Exception(
                    "Cannot register two routes matching \"{$tRegex}\" for method \"{$tMethod}\""
                );
            }
        }

        foreach ($uMethods as $tMethod) {
            $this->regexToRoutesMap[$tRegex][$tMethod] = [
                "method"    => $tMethod,
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
        $tRegexToRoutesMapCount = count($this->regexToRoutesMap);

        if ($tRegexToRoutesMapCount === 0) {
            $tVariableRouteData = [];
        } else {
            $tNumParts = max(1, round($tRegexToRoutesMapCount / self::APPROX_CHUNK_SIZE));
            $tChunkSize = ceil($tRegexToRoutesMapCount / $tNumParts);

            $tChunks = array_chunk($this->regexToRoutesMap, $tChunkSize, true);
            $tVariableRouteData = array_map([__CLASS__, "processChunk"], $tChunks);
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
    public function processChunk(array $uRegexToRoutesMap)
    {
        $tRouteMap = [];
        $tRegexes = [];
        $tNumGroups = 0;

        foreach ($uRegexToRoutesMap as $tRegex => $tRoutes) {
            $tFirstRoute = reset($tRoutes);
            $tNumVariables = count($tFirstRoute["variables"]);
            $tNumGroups = max($tNumGroups, $tNumVariables);

            $tRegexes[] = $tRegex . str_repeat("()", $tNumGroups - $tNumVariables);

            foreach ($tRoutes as $tRoute) {
                $tRouteMap[$tNumGroups + 1][$tRoute["method"]] = [$tRoute["callback"], $tRoute["variables"]];
            }

            ++$tNumGroups;
        }

        return [
            "regex"    => "~^(?|" . implode("|", $tRegexes) . ")$~",
            "routeMap" => $tRouteMap
        ];
    }
}
