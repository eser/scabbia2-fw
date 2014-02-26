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

namespace Scabbia\Framework;

use Scabbia\Events\Events;

/**
 * Default methods needed for implementation of an application
 *
 * @package     Scabbia\Framework
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
abstract class ApplicationBase
{
    /** @type ApplicationBase $current current application instance */
    public static $current;
    /** @type Events          $events events */
    public $events;
    /** @type string          $writablePath writable output folder */
    public $writablePath;
    /** @type bool            $development the development flag of application is on or off */
    public $development;
    /** @type bool            $disableCaches the disable caches flag of application is on or off */
    public $disableCaches;


    /**
     * Initializes an application
     *
     * @param mixed  $uOptions      options
     * @param string $uWritablePath writable output folder
     *
     * @return ApplicationBase
     */
    public function __construct($uOptions, $uWritablePath)
    {
        $this->writablePath = $uWritablePath;

        $this->development = $uOptions["development"];
        $this->disableCaches = $uOptions["disableCaches"];
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
    abstract public function generateRequest($uMethod, $uPathInfo, array $uQueryParameters, array $uPostParameters);

    /**
     * Generates request from globals
     *
     * @return void
     */
    abstract public function generateRequestFromGlobals();

    /**
     * Runs the application
     *
     * @return void
     */
    public function run()
    {
        $this->events = new Events();
        $this->events->events = require "{$this->writablePath}/events.php";

        // TODO initialize the proper environment
        // TODO instantiate application with variables (environment, application config [development, disableCaches])
        // TODO load modules
        // TODO execute autoexecs
    }
}
