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

namespace Scabbia\Views;

use Scabbia\Code\TokenStream;
use Scabbia\Generators\GeneratorBase;
use Scabbia\Views\Views;

/**
 * ViewGenerator
 *
 * @package     Scabbia\Views
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 *
 * @scabbia-generator
 */
class ViewGenerator extends GeneratorBase
{
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
        $tViewEngine = Views::findViewEngine($uPath);

        if ($tViewEngine === null) {
            return;
        }

        // TODO compile view
        $tViewEngineClass = get_class($tViewEngine);
        echo sprintf("View %s => %s\n", $uPath, $tViewEngineClass);
    }
}
