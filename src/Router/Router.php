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

use Scabbia\Framework\ApplicationBase;
use Scabbia\Framework\Core;

/**
 * Router
 *
 * @package     Scabbia\Router
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 *
 * Routing related code based on the nikic's FastRoute solution:
 * http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html
 */
class Router
{
    /** @type string VARIABLE_REGEX Regex expression of variables */
    const VARIABLE_REGEX = <<<'REGEX'
~\{
    \s* ([a-zA-Z][a-zA-Z0-9_]*) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}*])*)
    )?
\}~x
REGEX;

    /** @type string DEFAULT_DISPATCH_REGEX Regex expression of default dispatch */
    const DEFAULT_DISPATCH_REGEX = "[^/]+";

    /** @type int FOUND              route found */
    const FOUND = 0;
    /** @type int NOT_FOUND          route not found */
    const NOT_FOUND = 1;
    /** @type int METHOD_NOT_ALLOWED route method is not allowed */
    const METHOD_NOT_ALLOWED = 2;


    /** @type null|array route definitions */
    public static $routes = null;


    /**
     * The dispatch method
     *
     * @param string $uMethod   http method
     * @param string $uPathInfo path
     *
     * @return mixed
     */
    public static function dispatch($uMethod, $uPathInfo)
    {
        if (self::$routes === null) {
            $tRoutesFilePath = ApplicationBase::$current->writablePath . "/routes.php";
            self::$routes = require $tRoutesFilePath;
        }

        if (isset(self::$routes["static"][$uPathInfo])) {
            $tRoute = self::$routes["static"][$uPathInfo];

            if (isset($tRoute[$uMethod])) {
                return [
                    "status"     => self::FOUND,
                    "callback"   => $tRoute[$uMethod],
                    "parameters" => []
                ];
            } elseif ($uMethod === "HEAD" && isset($tRoute["GET"])) {
                return [
                    "status"     => self::FOUND,
                    "callback"   => $tRoute["GET"],
                    "parameters" => []
                ];
            } else {
                return [
                    "status"     => self::METHOD_NOT_ALLOWED,
                    "methods"    => array_keys($tRoute)
                ];
            }
        }

        foreach (self::$routes["variable"] as $tVariableRoute) {
            if (preg_match($tVariableRoute["regex"], $uPathInfo, $tMatches) !== 1) {
                continue;
            }

            $tRoute = $tVariableRoute["routeMap"][count($tMatches)];
            if (!isset($tRoute[$uMethod])) {
                if ($uMethod === "HEAD" && isset($tRoute["GET"])) {
                    $uMethod = "GET";
                } else {
                    return [
                        "status"     => self::METHOD_NOT_ALLOWED,
                        "methods"    => array_keys($tRoute)
                    ];
                }
            }

            list($tCallback, $tVariableNames) = $tRoute[$uMethod];

            $tVariables = [];
            $tCount = 0;
            foreach ($tVariableNames as $tVariableName) {
                $tVariables[$tVariableName] = $tMatches[++$tCount];
            }

            return [
                "status"     => self::FOUND,
                "callback"   => $tCallback,
                "parameters" => $tVariables
            ];
        }

        return [
            "status"     => self::NOT_FOUND
        ];
    }

    /**
     * Parses routes of the following form:
     * "/user/{name}/{id:[0-9]+}"
     *
     * @param string $uRoute route pattern
     *
     * @return array
     */
    public static function parse($uRoute)
    {
        if (!preg_match_all(self::VARIABLE_REGEX, $uRoute, $tMatches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return [$uRoute];
        }

        $tOffset = 0;
        $tRouteData = [];
        foreach ($tMatches as $tMatch) {
            if ($tMatch[0][1] > $tOffset) {
                $tRouteData[] = substr($uRoute, $tOffset, $tMatch[0][1] - $tOffset);
            }

            $tRouteData[] = [
                $tMatch[1][0],
                isset($tMatch[2]) ? trim($tMatch[2][0]) : self::DEFAULT_DISPATCH_REGEX
            ];

            $tOffset = $tMatch[0][1] + strlen($tMatch[0][0]);
        }

        if ($tOffset !== strlen($uRoute)) {
            $tRouteData[] = substr($uRoute, $tOffset);
        }

        return $tRouteData;
    }

    /**
     * Generates a path using named routes
     *
     * @param string $uName        name of route
     * @param array  $uParameters  parameters
     *
     * @return false|string
     */
    public static function path($uName, array $uParameters = [])
    {
        if (self::$routes === null) {
            $tRoutesFilePath = ApplicationBase::$current->writablePath . "/routes.php";
            self::$routes = require $tRoutesFilePath;
        }

        if (!isset(self::$routes["named"][$uName])) {
            return false;
        }

        $tNamedRoute = self::$routes["named"][$uName];
        $tLink = $tNamedRoute[0];
        foreach ($tNamedRoute[1] as $tParameter) {
            if (isset($uParameters[$tParameter])) {
                $tValue = $uParameters[$tParameter];
            } else {
                $tValue = "";
            }

            $tLink = str_replace("{{$tParameter}}", $tValue, $tLink);
        }

        return $tLink;
    }
}
