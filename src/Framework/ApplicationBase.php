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

namespace Scabbia\Framework;

use Scabbia\LightStack\ApplicationInterface;
use Scabbia\LightStack\RequestInterface;
use Scabbia\LightStack\ResponseInterface;
use Scabbia\Events\Events;

/**
 * Default methods needed for implementation of an application
 *
 * @package     Scabbia\Framework
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
abstract class ApplicationBase implements ApplicationInterface
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
     * Generates request
     *
     * @param string $uMethod          method
     * @param string $uPathInfo        pathinfo
     * @param array  $uQueryParameters query parameters
     *
     * @return RequestInterface request object
     */
    abstract public function generateRequest($uMethod, $uPathInfo, array $uQueryParameters);

    /**
     * Generates request from globals
     *
     * @return RequestInterface request object
     */
    abstract public function generateRequestFromGlobals();

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
