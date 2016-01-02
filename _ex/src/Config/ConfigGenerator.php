<?php
/**
 * Scabbia2 PHP Framework Code
 * https://github.com/eserozvataf/scabbia2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/eserozvataf/scabbia2-fw for the canonical source repository
 * @copyright   2010-2016 Eser Ozvataf. (http://eser.ozvataf.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Config;

use Scabbia\Code\TokenStream;
use Scabbia\Config\Config;
use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Generators\GeneratorRegistry;
use Scabbia\Helpers\FileSystem;

/**
 * ConfigGenerator
 *
 * @package     Scabbia\Config
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 *
 * @scabbia-generator
 *
 * @todo FIXME include application configuration from project.yml ?
 */
class ConfigGenerator extends GeneratorBase
{
    /** @type Config $unifiedConfig unified configuration */
    public $unifiedConfig;


    /**
     * Initializes a generator
     *
     * @param GeneratorRegistry  $uGeneratorRegistry   generator registry
     *
     * @return GeneratorBase
     */
    public function __construct(GeneratorRegistry $uGeneratorRegistry)
    {
        parent::__construct($uGeneratorRegistry);

        $this->unifiedConfig = new Config();
    }

    /**
     * Processes a file
     *
     * @param string      $uPath         file path
     * @param string      $uFileContents contents of file
     * @param TokenStream $uTokenStream  extracted tokens wrapped with tokenstream
     *
     * @return void
     */
    public function processFile($uPath, $uFileContents, TokenStream $uTokenStream)
    {
        if (substr($uPath, -11) !== ".config.yml") {
            return;
        }

        $this->unifiedConfig->add($uPath);
        echo "Config {$uPath}\n";
    }

    /**
     * Finalizes generator process
     *
     * @return void
     */
    public function finalize()
    {
        $this->generatorRegistry->saveFile(
            "unified-config.php",
            $this->unifiedConfig->get(),
            true
        );
    }
}
