<?php
/**
 * Scabbia2 PHP Framework Code
 * https://github.com/eserozvataf/scabbia2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/eserozvataf/scabbia2-fw for the canonical source repository
 * @copyright   2010-2016 Eser Ozvataf. (http://eser.ozvataf.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Framework;

use Scabbia\Events\Events;
use Scabbia\LightStack\MiddlewareInterface;
use Scabbia\LightStack\Request;
use Scabbia\LightStack\RequestInterface;
use Scabbia\LightStack\ResponseInterface;

/**
 * Default methods needed for implementation of an application
 *
 * @package     Scabbia\Framework
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 */
abstract class ApplicationBase implements MiddlewareInterface
{
    /** @type ApplicationBase $current current application instance */
    public static $current = null;
    /** @type mixed           $config application configuration */
    public $config;
    /** @type Events          $events events */
    public $events;
    /** @type string          $writablePath writable output folder */
    public $writablePath;
    /** @type bool            $development the development flag of application is on or off */
    public $development;


    /**
     * Initializes an application
     *
     * @param mixed  $uConfig        application config
     * @param string $uWritablePath  writable output folder
     *
     * @return ApplicationBase
     */
    public function __construct($uConfig, $uWritablePath)
    {
        $this->writablePath = $uWritablePath;

        $this->config = $uConfig;
        $this->development = $uConfig["development"];

        $this->events = new Events();
        if (file_exists($tFile = "{$this->writablePath}/events.php")) {
            $this->events->events = require $tFile;
        }

        // TODO initialize the proper environment
        if ($this->development) {
            error_reporting(-1);
        } else {
            error_reporting(0);
        }

        // TODO set exception handler
        // TODO instantiate application with variables (environment and its config [development, disableCaches])
        // TODO load modules
        // TODO execute autoexecs
    }

    /**
     * Generates a request object
     *
     * @param string      $uMethod            method
     * @param string      $uPathInfo          pathinfo
     * @param array|null  $uDetails           available keys: get, post, files, server, session, cookies, headers
     *
     * @return RequestInterface request object
     */
    public function generateRequest($uMethod, $uPathInfo, array $uDetails = null)
    {
        return new Request($uMethod, $uPathInfo, $uDetails);
    }

    /**
     * Generates a request object from globals
     *
     * @return RequestInterface request object
     */
    public function generateRequestFromGlobals()
    {
        return Request::generateFromGlobals();
    }

    /**
     * Handles a request
     *
     * @param RequestInterface $uRequest        request object
     * @param bool             $uIsSubRequest   whether is a sub-request or not
     *
     * @return ResponseInterface response object
     */
    abstract public function handleRequest(RequestInterface $uRequest, $uIsSubRequest);
}
