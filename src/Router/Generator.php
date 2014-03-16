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
use Scabbia\Framework\Io;
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
class Generator
{
    /** @type string FILTER_VALIDATE_BOOLEAN a symbolic constant for boolean validation */
    const APPROX_CHUNK_SIZE = 10;


    /** @type array $staticRoutes set of static routes */
    public static $staticRoutes = [];
    /** @type array $regexToRoutesMap map of variable routes */
    public static $regexToRoutesMap = [];
    /** @type array $namedRoutes map of named routes */
    public static $namedRoutes = [];


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
    public static function addRoute($uMethods, $uRoute, $uCallback, $uName = null)
    {
        $tRouteData = Router::parse($uRoute);
        $tMethods = (array)$uMethods;

        if (count($tRouteData) === 1 && is_string($tRouteData[0])) {
            self::addStaticRoute($tMethods, $tRouteData, $uCallback, $uName);
        } else {
            self::addVariableRoute($tMethods, $tRouteData, $uCallback, $uName);
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
    public static function addStaticRoute(array $uMethods, $uRouteData, $uCallback, $uName = null)
    {
        $tRouteStr = $uRouteData[0];

        foreach ($uMethods as $tMethod) {
            if (isset(self::$staticRoutes[$tRouteStr][$tMethod])) {
                throw new \Exception(
                    "Cannot register two routes matching \"{$tRouteStr}\" for method \"{$tMethod}\""
                );
            }
        }

        foreach ($uMethods as $tMethod) {
            foreach (self::$regexToRoutesMap as $tRoutes) {
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

            self::$staticRoutes[$tRouteStr][$tMethod] = $uCallback;

            if ($uName !== null) {
                if (!isset(self::$namedRoutes[$tMethod])) {
                    self::$namedRoutes[$tMethod] = [];
                }

                self::$namedRoutes[$tMethod][$uName] = [$tRouteStr, []];
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
    public static function addVariableRoute(array $uMethods, $uRouteData, $uCallback, $uName = null)
    {
        $tRegex = "";
        $tVariables = [];

        foreach ($uRouteData as $tPart) {
            if (is_string($tPart)) {
                $tRegex .= preg_quote($tPart, "~");
                continue;
            }

            list($tVariableName, $tRegexPart) = $tPart;

            if (isset($tVariables[$tVariableName])) {
                throw new \Exception("Cannot use the same placeholder \"{$tVariableName}\" twice");
            }

            $tVariables[$tVariableName] = $tVariableName;
            $tRegex .= "({$tRegexPart})";
        }

        foreach ($uMethods as $tMethod) {
            if (isset(self::$regexToRoutesMap[$tRegex][$tMethod])) {
                throw new \Exception(
                    "Cannot register two routes matching \"{$tRegex}\" for method \"{$tMethod}\""
                );
            }
        }

        foreach ($uMethods as $tMethod) {
            self::$regexToRoutesMap[$tRegex][$tMethod] = [
                "method"    => $tMethod,
                "callback"  => $uCallback,
                "regex"     => $tRegex,
                "variables" => $tVariables
            ];

            if ($uName !== null) {
                if (!isset(self::$namedRoutes[$tMethod])) {
                    self::$namedRoutes[$tMethod] = [];
                }

                self::$namedRoutes[$tMethod][$uName] = [$tRegex, $tVariables];
            }
        }
    }

    /**
     * Combines all route data in order to return it as a result of generation process
     *
     * @return array data
     */
    public static function getData()
    {
        $tRegexToRoutesMapCount = count(self::$regexToRoutesMap);

        if ($tRegexToRoutesMapCount === 0) {
            $tVariableRouteData = [];
        } else {
            $tNumParts = max(1, round($tRegexToRoutesMapCount / self::APPROX_CHUNK_SIZE));
            $tChunkSize = ceil($tRegexToRoutesMapCount / $tNumParts);

            $tChunks = array_chunk(self::$regexToRoutesMap, $tChunkSize, true);
            $tVariableRouteData = array_map([__CLASS__, "processChunk"], $tChunks);
        }

        return [
            "static"   => self::$staticRoutes,
            "variable" => $tVariableRouteData,
            "named"    => self::$namedRoutes
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

    /**
     * Entry point for processor
     *
     * @param array  $uAnnotations  annotations
     * @param string $uWritablePath writable output folder
     *
     * @return void
     */
    public static function generate(array $uAnnotations, $uWritablePath)
    {
        foreach ($uAnnotations as $tClassKey => $tClass) {
            foreach ($tClass["methods"] as $tMethodKey => $tMethod) {
                if (!isset($tMethod["route"])) {
                    continue;
                }

                foreach ($tMethod["route"] as $tRoute) {
                    self::addRoute(
                        $tRoute["method"],
                        $tRoute["path"],
                        [$tClassKey, $tMethodKey],
                        isset($tRoute["name"]) ? $tRoute["name"] : null
                    );
                }
            }
        }

        Io::writePhpFile("{$uWritablePath}/routes.php", self::getData());
    }
}
