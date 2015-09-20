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

namespace Scabbia\Framework;

use Scabbia\Config\ConfigCollection;

/**
 * Project
 *
 * @package     Scabbia\Framework
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 *
 * @todo pickApplication and pickEnvironment delegates
 */
// MD-TITLE Project Class
class Project
{
    /** @type Project           $instance   the singleton instance of project */
    public static $instance = null;
    /** @type mixed             $loader     the instance of the autoloader class */
    public $loader;
    /** @type ConfigCollection  $config     configuration */
    public $config = new ConfigCollection();


    /**
     * Initializes a new instance of Project class
     *
     * @param mixed $uLoader The instance of the autoloader class
     *
     * @return Project
     */
    public function __construct($uLoader)
    {
        if (static::$instance === null) {
            static::$instance = $this;
        }

        // MD assign autoloader to Project::$loader
        $this->loader = $uLoader;
    }
}
