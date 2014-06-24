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
use Scabbia\Router\Router;
use Scabbia\Helpers\String;

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
     * @param mixed  $uOptions      options
     * @param array  $uPaths        paths include source files
     * @param string $uWritablePath writable output folder
     *
     * @return Application
     */
    public function __construct($uOptions, $uPaths, $uWritablePath)
    {
        parent::__construct($uOptions, $uPaths, $uWritablePath);

        $this->events->invoke("applicationInit");
    }

    /**
     * Gets request method
     *
     * @return array
     */
    public function getRequestMethod()
    {
        // not implemented yet
        return "get";
    }

    /**
     * Gets request path info
     *
     * @return array
     */
    public function getRequestPathInfo()
    {
        // not implemented yet
        if (isset($_GET["q"])) {
            return $_GET["q"];
        }

        return "";
    }

    /**
     * Gets query parameters
     *
     * @return array
     */
    public function getQueryParameters()
    {
        // not implemented yet
        return $_GET;
    }

    /**
     * Gets post parameters
     *
     * @return array
     */
    public function getPostParameters()
    {
        // not implemented yet
        return $_POST;
    }

    /**
     * Generates request
     *
     * @param string $uMethod          method
     * @param string $uPathInfo        pathinfo
     * @param array  $uQueryParameters query parameters
     * @param array  $uPostParameters  post parameters
     *
     * @throws \Exception if routing fails
     * @return void
     */
    public function generateRequest($uMethod, $uPathInfo, array $uQueryParameters, array $uPostParameters)
    {
        $tRoute = Router::dispatch($uMethod, $uPathInfo);
        $tRequestData = [
            "method"          => $uMethod,
            "pathinfo"        => $uPathInfo,
            "queryParameters" => $uQueryParameters,
            "postParameters"  => $uPostParameters,
            "route"           => $tRoute
        ];

        $this->events->invoke("requestBegin", $tRequestData);
        if ($tRoute[0] === Router::FOUND) {
            // push some variables like named parameters
            call_user_func_array($tRoute[1], $tRoute[2]);
            // pop previously pushed variables
        } elseif ($tRoute[0] === Router::METHOD_NOT_ALLOWED) {
            // TODO exception
            throw new \Exception("");
        } elseif ($tRoute[0] === Router::NOT_FOUND) {
            // TODO exception
            throw new \Exception("");
        }

        $this->events->invoke("requestEnd", $tRequestData);
    }

    /**
     * Generates request from globals
     *
     * @return void
     */
    public function generateRequestFromGlobals()
    {
        $this->generateRequest(
            $this->getRequestMethod(),
            $this->getRequestPathInfo(),
            $this->getQueryParameters(),
            $this->getPostParameters()
        );
    }
}
