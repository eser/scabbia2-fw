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

namespace Scabbia\Mvc;

use Scabbia\Framework\ApplicationBase;
use Scabbia\Framework\Core;
use Scabbia\Helpers\String;
use Scabbia\Router\Router;
use Scabbia\LightStack\RequestInterface;
use Exception;

/**
 * Application Implementation for MVC layered architecture
 *
 * @package     Scabbia\Mvc
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class Application extends ApplicationBase
{
    /**
     * Initializes an application
     *
     * @param mixed  $uConfig       application config
     * @param string $uWritablePath writable output folder
     *
     * @return Application
     */
    public function __construct($uConfig, $uWritablePath)
    {
        parent::__construct($uConfig, $uWritablePath);

        // remote host
        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            Core::$variables["http-remotehost"] = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["REMOTE_ADDR"])) {
            Core::$variables["http-remotehost"] = $_SERVER["REMOTE_ADDR"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            Core::$variables["http-remotehost"] = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            Core::$variables["http-remotehost"] = "0.0.0.0";
        }

        // http method
        if (isset($_SERVER["X-HTTP-METHOD-OVERRIDE"])) {
            Core::$variables["http-method"] = strtolower($_SERVER["X-HTTP-METHOD-OVERRIDE"]);
        } elseif (isset($_POST["_method"])) {
            Core::$variables["http-method"] = strtolower($_POST["_method"]);
        } else {
            Core::$variables["http-method"] = strtolower($_SERVER["REQUEST_METHOD"]);
        }

        // http requested with
        if (isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
            Core::$variables["http-requested-with"] = strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]);
        } else {
            Core::$variables["http-requested-with"] = null;
        }

        // http accept language
        // TODO not implemented

        // http accept content-types
        // TODO not implemented

        // http original url
        if (isset($_SERVER["X_ORIGINAL_URL"])) {
            Core::$variables["http-request-uri"] = $_SERVER["X_ORIGINAL_URL"];
        } elseif (isset($_SERVER["X_REWRITE_URL"])) {
            Core::$variables["http-request-uri"] = $_SERVER["X_REWRITE_URL"];
        } elseif (isset($_SERVER["HTTP_X_REWRITE_URL"])) {
            Core::$variables["http-request-uri"] = $_SERVER["HTTP_X_REWRITE_URL"];
        } elseif (isset($_SERVER["IIS_WasUrlRewritten"]) && (string)$_SERVER["IIS_WasUrlRewritten"] === "1" &&
            isset($_SERVER["UNENCODED_URL"])) {
            Core::$variables["http-request-uri"] = $_SERVER["UNENCODED_URL"];
        } elseif (isset($_SERVER["REQUEST_URI"])) {
            if (strncmp(
                $_SERVER["REQUEST_URI"],
                Core::$variables["host"],
                $tHostLength = strlen(Core::$variables["host"])
            ) === 0) {
                Core::$variables["http-request-uri"] = substr($_SERVER["REQUEST_URI"], $tHostLength);
            } else {
                Core::$variables["http-request-uri"] = $_SERVER["REQUEST_URI"];
            }
        } elseif (isset($_SERVER["ORIG_PATH_INFO"])) {
            Core::$variables["http-request-uri"] = $_SERVER["ORIG_PATH_INFO"];

            if (isset($_SERVER["QUERY_STRING"]) && strlen($_SERVER["QUERY_STRING"]) > 0) {
                Core::$variables["http-request-uri"] .= "?" . $_SERVER["QUERY_STRING"];
            }
        } else {
            Core::$variables["http-request-uri"] = "";
        }

        // http pathroot
        if (!isset(Core::$variables["http-pathroot"])) {
            Core::$variables["http-pathroot"] = pathinfo($_SERVER["SCRIPT_NAME"], PATHINFO_DIRNAME);
        }
        Core::$variables["http-pathroot"] = trim(str_replace("\\", "/", Core::$variables["http-pathroot"]), "/");
        if (strlen(Core::$variables["http-pathroot"]) > 0) {
            Core::$variables["http-pathroot"] = "/" . Core::$variables["http-pathroot"];
        }

        // http pathinfo
        if (($tPos = strpos(Core::$variables["http-request-uri"], "?")) !== false) {
            $tBaseUriPath = substr(Core::$variables["http-request-uri"], 0, $tPos);
        } else {
            $tBaseUriPath = Core::$variables["http-request-uri"];
        }

        Core::$variables["http-pathinfo"] = substr($tBaseUriPath, strlen(Core::$variables["http-pathroot"]));

        Core::updateVariablesCache();

        $this->events->invoke("applicationInit");
    }

    /**
     * Gets request method
     *
     * @return array
     */
    public function getRequestMethod()
    {
        return Core::$variables["http-method"];
    }

    /**
     * Gets request path info
     *
     * @return array
     */
    public function getRequestPathInfo()
    {
        return Core::$variables["http-pathinfo"];
    }

    /**
     * Gets query parameters
     *
     * @return array
     */
    public function getQueryParameters()
    {
        return $_GET;
    }

    /**
     * Gets post parameters
     *
     * @return array
     */
    public function getPostParameters()
    {
        return $_POST;
    }

    /**
     * Generates request
     *
     * @param string $uMethod          method
     * @param string $uPathInfo        pathinfo
     * @param array  $uQueryParameters query parameters
     *
     * @throws Exception if routing fails
     * @return RequestInterface request object
     */
    public function generateRequest($uMethod, $uPathInfo, array $uQueryParameters)
    {
        $tRoute = Router::dispatch($uMethod, $uPathInfo);

        $tModule = "front";
        foreach ($this->config["modules"] as $tModuleKey => $tModuleDefinition) {
            if (strncmp($uPathInfo, "/{$tModuleKey}/", strlen($tModuleKey) + 2) === 0) {
                $tModule = $tModuleKey;
                break;
            }
        }

        $tRoute += [
            "method"          => $uMethod,
            "module"          => $tModule,
            "pathinfo"        => $uPathInfo,
            "queryParameters" => $uQueryParameters
        ];

        $this->events->invoke("requestBegin", $tRoute);
        if ($tRoute["status"] === Router::FOUND) {
            // push some variables like named parameters
            $tInstance = new $tRoute["callback"][0] ();

            $tInstance->routeInfo = $tRoute;
            $tInstance->applicationConfig = $this->config;
            if (isset($this->config["modules"][$tModule]["config"])) {
                $tInstance->moduleConfig = $this->config["modules"][$tModule]["config"];
            } else {
                $tInstance->moduleConfig = null;
            }

            $tInstance->prerender->invoke();
            call_user_func_array([&$tInstance, $tRoute["callback"][1]], $tRoute["parameters"]);
            $tInstance->postrender->invoke();
            // pop previously pushed variables
        } elseif ($tRoute["status"] === Router::METHOD_NOT_ALLOWED) {
            // TODO exception
            throw new Exception("");
        } elseif ($tRoute["status"] === Router::NOT_FOUND) {
            // TODO exception
            throw new Exception("");
        }

        $this->events->invoke("requestEnd", $tRoute);
    }

    /**
     * Generates request from globals
     *
     * @return RequestInterface request object
     */
    public function generateRequestFromGlobals()
    {
        return $this->generateRequest(
            $this->getRequestMethod(),
            $this->getRequestPathInfo(),
            $this->getQueryParameters()
        );
    }

    /**
     * Processes a request
     *
     * @param RequestInterface $uRequest        request object
     * @param bool             $uIsSubRequest   whether is a sub-request or not
     *
     * @return ResponseInterface response object
     */
    public function processRequest(RequestInterface $uRequest, $uIsSubRequest)
    {
        // TODO move generate request code here
    }
}
