<?php
/**
 * Scabbia2 PHP Framework Code
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/scabbiafw/scabbia2-fw for the canonical source repository
 * @copyright   2010-2015 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Views;

/**
 * ViewEngineBase
 *
 * @package     Scabbia\Views
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 *
 * @todo compile
 * @todo sections
 */
class ViewEngineBase
{
    /** @type mixed $model model for the current view */
    public $model;
    /** @type object|null $controller controller for the current view */
    public $controller;

    /**
     * Initializes a view engine
     *
     * @return ViewEngineBase
     */
    public function __construct()
    {
    }

    /**
     * Renders plain PHP file for using them as a template format
     *
     * @param string $tTemplatePath path of the template file
     * @param string $tTemplateFile filename of the template file
     * @param mixed  $uModel        model object
     * @param mixed  $uController   controller instance
     *
     * @return void
     */
    public function render($tTemplatePath, $tTemplateFile, $uModel = null, $uController = null)
    {
        $this->model = $uModel;
        $this->controller = $uController;

        if ($uModel !== null && is_array($uModel)) {
            extract($uModel, EXTR_SKIP | EXTR_REFS);
        }

        include "{$tTemplatePath}{$tTemplateFile}";
    }
}
