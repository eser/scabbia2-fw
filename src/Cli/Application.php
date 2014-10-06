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

namespace Scabbia\Cli;

use Scabbia\Framework\ApplicationBase;
use Scabbia\LightStack\RequestInterface;

/**
 * Application Implementation for Command Line Interface
 *
 * @package     Scabbia\Cli
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class Application extends ApplicationBase
{
    /**
     * Initializes an application
     *
     * @param mixed  $uConfig        application config
     * @param string $uWritablePath  writable output folder
     *
     * @return Application
     */
    public function __construct($uConfig, $uWritablePath)
    {
        parent::__construct($uConfig, $uWritablePath);
    }

    /**
     * Gets request method
     *
     * @return array
     */
    public function getRequestMethod()
    {
        return "GET";
    }

    /**
     * Gets request path info
     *
     * @return array
     */
    public function getRequestPathInfo()
    {
        return "/";
    }

    /**
     * Gets query parameters
     *
     * @return array
     */
    public function getQueryParameters()
    {
        return [];
    }

    /**
     * Gets post parameters
     *
     * @return array
     */
    public function getPostParameters()
    {
        return [];
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
    public function generateRequest($uMethod, $uPathInfo, array $uQueryParameters)
    {
        // TODO get command line arguments
        // TODO determine module
        // TODO fire begin events
        // TODO execute application
        // TODO fire end events
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
