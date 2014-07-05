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

use Scabbia\Framework\Core;
use Scabbia\Helpers\FileSystem;
use Scabbia\Tasks\TaskBase;

/**
 * Task class for "php scabbia clean"
 *
 * @package     Scabbia\Framework
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class CleanTask extends TaskBase
{

    /**
     * Initializes the clean task
     *
     * @param mixed      $uConfig    configuration
     * @param IInterface $uInterface interface class
     *
     * @return CleanTask
     */
    public function __construct($uConfig, $uInterface)
    {
        parent::__construct($uConfig, $uInterface);
    }

    /**
     * Executes the task
     *
     * @param array $uParameters parameters
     *
     * @return int exit code
     */
    public function executeTask(array $uParameters)
    {
        $tPath = Core::$basepath . "/writable/cache";
        FileSystem::garbageCollect($tPath, ["dotFiles" => false]);

        $this->interface->writeColor("yellow", "done.");

        return 0;
    }
}
