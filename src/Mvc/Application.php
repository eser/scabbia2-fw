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

    }

    /**
     * Generates request from globals
     *
     * @return void
     */
    public function generateRequestFromGlobals()
    {
        // $this->generateRequest('GET', 'home/index', $_GET, $_POST);
    }

    /**
     * Runs the application
     *
     * @return void
     */
    public function run()
    {
        parent::run();
    }
}
