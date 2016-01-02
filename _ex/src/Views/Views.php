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

namespace Scabbia\Views;

use Scabbia\Framework\Core;
use UnexpectedValueException;

/**
 * Views
 *
 * @package     Scabbia\Views
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       1.0.0
 */
class Views
{
    /** @type array $engines set of engines */
    public static $engines = [
        ".view.php" => ["Scabbia\\Views\\ViewEngineBase", null]
    ];


    /**
     * Constructor to prevent new instances of Views class
     *
     * @return Views
     */
    final private function __construct()
    {
    }

    /**
     * Clone method to prevent duplication of Views class
     *
     * @return Views
     */
    final private function __clone()
    {
    }

    /**
     * Unserialization method to prevent restoration of views class
     *
     * @return Views
     */
    final private function __wakeup()
    {
    }

    /**
     * Finds the associated view engine for a filename
     *
     * @param string $uFilename filename
     *
     * @return object|null the instance for the view engine
     */
    public static function findViewEngine($uFilename)
    {
        foreach (self::$engines as $tEngineKey => &$tEngine) {
            if (substr($uFilename, -strlen($tEngineKey)) === $tEngineKey) {
                if ($tEngine[1] === null) {
                    $tEngine[1] = new $tEngine[0] ();
                }

                return $tEngine[1];
            }
        }

        return null;
    }

    /**
     * Renders a view
     *
     * @param string $uView       view file
     * @param mixed  $uModel      view model
     * @param mixed  $uController controller instance
     *
     * @throws UnexpectedValueException if any render engine is not associated with the extension
     * @return void
     */
    public static function viewFile($uView, $uModel = null, $uController = null)
    {
        $tViewFilePath = Core::$instance->loader->findResource($uView);
        $tViewFileInfo = pathinfo($tViewFilePath);

        $tViewEngine = self::findViewEngine($tViewFilePath);

        if ($tViewEngine === null) {
            // TODO exception
            throw new UnexpectedValueException("");
        }

        $tViewEngine->render("{$tViewFileInfo["dirname"]}/", $tViewFileInfo["basename"], $uModel, $uController);
    }
}
