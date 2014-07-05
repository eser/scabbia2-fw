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

use Scabbia\Containers\BindableContainer;
use Scabbia\Events\Delegate;
use Scabbia\Objects\Collection;
use Scabbia\Views\Views;

/**
 * Controller class template
 *
 * @package     Scabbia\Mvc
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 */
abstract class Controller
{
    use BindableContainer;

    /** @type array $routeInfo routing information */
    public $routeInfo;
    /** @type mixed $applicationConfig application configuration */
    public $applicationConfig;
    /** @type mixed $moduleConfig module configuration */
    public $moduleConfig;
    /** @type Collection $vars variables */
    public $vars;
    /** @type Delegate $prerender prerender hook */
    public $prerender;
    /** @type Delegate $postrender postrender hook */
    public $postrender;


    /**
     * Initializes a Controller class instance
     *
     * @return Controller
     */
    public function __construct()
    {
        $this->vars = new Collection();
        $this->prerender = new Delegate();
        $this->postrender = new Delegate();
    }

    /**
     * Renders a view
     *
     * @param string $uView  view file
     * @param mixed  $uModel view model
     *
     * @return void
     */
    public function view($uView, $uModel = null)
    {
        if ($uModel === null) {
            $uModel = $this->vars->toArray();
        }

        $uModel["controller"] = $this;

        $tNamespace = $this->applicationConfig["modules"][$this->routeInfo["module"]]["namespace"];
        Views::viewFile("{$tNamespace}\\Views\\{$uView}", $uModel);
    }
}
