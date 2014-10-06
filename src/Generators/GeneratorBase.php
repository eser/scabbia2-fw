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

/**
 * Default methods needed for implementation of a generator
 *
 * @package     Scabbia\Generators
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
abstract class GeneratorBase
{
    /** @type array       $annotations        annotations to be processed */
    public $annotations;
    /** @type mixed       $applicationConfig  application configuration */
    public $applicationConfig;
    /** @type string      $outputPath         output path for generated files */
    public $outputPath;


    /**
     * Initializes a generator
     *
     * @param mixed  $uApplicationConfig application config
     * @param string $uOutputPath        output path
     *
     * @return GeneratorBase
     */
    public function __construct($uApplicationConfig, $uOutputPath)
    {
        $this->applicationConfig = $uApplicationConfig;
        $this->outputPath = $uOutputPath;
    }

    /**
     * Initializes generator
     *
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * Processes a file
     *
     * @param string $uPath         file path
     * @param string $uFileContents contents of file
     * @param string $uTokenStream  extracted tokens wrapped with tokenstream
     *
     * @return void
     */
    public function processFile($uPath, $uFileContents, $uTokenStream)
    {
    }

    /**
     * Processes set of annotations
     *
     * @param array $uAnnotations annotations
     *
     * @return void
     */
    public function processAnnotations($uAnnotations)
    {
    }

    /**
     * Finalizes generator
     *
     * @return void
     */
    public function finalize()
    {
    }
}
