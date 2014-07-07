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

namespace Scabbia\Tasks;

use Scabbia\Objects\Interpreter;

/**
 * Default methods needed for implementation of a task
 *
 * @package     Scabbia\Tasks
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 *
 * @todo "scabbia help <task>" task
 */
abstract class TaskBase
{
    /** @type mixed           $config      task configuration */
    public $config;
    /** @type IInterface      $interface   output class */
    public $interface;


    /**
     * Registers the tasks itself to an interpreter instance
     *
     * @param Interpreter $uInterpreter interpreter to be registered at
     *
     * @return void
     */
    public static function registerToInterpreter(Interpreter $uInterpreter)
    {
    }

    /**
     * Initializes a task
     *
     * @param mixed      $uConfig    configuration
     * @param IInterface $uInterface interface class
     *
     * @return TaskBase
     */
    public function __construct($uConfig, $uInterface)
    {
        $this->config = $uConfig;
        $this->interface = $uInterface;
    }

    /**
     * Executes the task
     *
     * @param array $uParameters parameters
     *
     * @return int exit code
     */
    abstract public function executeTask(array $uParameters);
}
