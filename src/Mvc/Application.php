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
     * Generates request
     *
     * @param string $uMethod          method
     * @param string $uPathInfo        pathinfo
     * @param array  $uQueryParameters query parameters
     * @param array  $uPostParameters  post parameters
     *
     * @return void
     */
    public function generateRequest($uMethod, $uPathInfo, array $uQueryParameters, array $uPostParameters)
    {
        $tDispatch = Router::dispatch($uMethod, $uPathInfo);

        String::vardump($tDispatch);

        String::vardump(Router::path("home/user", ["id" => "eser"]));

        $tDiff = (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]);
        echo "Generated in {$tDiff} msec";
    }

    /**
     * Generates request from globals
     *
     * @return void
     */
    public function generateRequestFromGlobals()
    {
        if (isset($_GET["q"])) {
            $this->generateRequest("get", $_GET["q"], $_GET, $_POST);
        }
    }
}
