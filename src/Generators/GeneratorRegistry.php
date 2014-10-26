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

namespace Scabbia\Generators;

use Scabbia\Code\AnnotationManager;
use Scabbia\Framework\Core;
use Scabbia\Helpers\FileSystem;
use RuntimeException;

/**
 * Task class for "php scabbia generate"
 *
 * @package     Scabbia\Generators
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class GeneratorRegistry
{
    /** @type array                   $generators         set of generators */
    public $generators = [];
    /** @type AnnotationManager|null  $annotationManager  annotation manager */
    public $annotationManager = null;


    /**
     * Executes the task
     *
     * @param array $uParameters parameters
     *
     * @throws RuntimeException if configuration is invalid
     * @return int exit code
     */
    public function scan()
    {
        $this->annotationManager = new AnnotationManager();
        $this->load();

        $this->get("scabbia-generator");
    }
}
