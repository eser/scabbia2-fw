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

namespace Scabbia\Config;

use Scabbia\Code\TokenStream;
use Scabbia\Config\Config;
use Scabbia\Framework\Core;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Helpers\FileSystem;

/**
 * ConfigGenerator
 *
 * @package     Scabbia\Config
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 *
 * @scabbia-generator
 *
 * @todo FIXME include application configuration from project.yml ?
 */
class ConfigGenerator extends GeneratorBase
{
    /** @type array $annotations set of annotations */
    public $annotations = [];
    /** @type Config $unifiedConfig unified configuration */
    public $unifiedConfig;


    /**
     * Initializes generator
     *
     * @return void
     */
    public function initialize()
    {
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
     * Dumps generated data into file
     *
     * @return void
     */
    public function dump()
    {
        FileSystem::writePhpFile(
            Core::translateVariables($this->application->writablePath . "/unified-config.php"),
            $this->unifiedConfig->get()
        );
    }
}
