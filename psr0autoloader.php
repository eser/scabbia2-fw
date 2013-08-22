<?php
/**
 * Scabbia2 PHP Framework
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        http://github.com/scabbiafw/scabbia2 for the canonical source repository
 * @copyright   Copyright (c) 2010-2013 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

/**
 * PSR-0 Autoloader Function
 *
 * @param $className string The class is going to be loaded.
 */
function autoload($className)
{
    $fileName = "";
    if ($lastNsPos = strrpos($className, "\\")) {
        $namespace = ltrim(substr($className, 0, $lastNsPos), "\\");
        $className = substr($className, $lastNsPos + 1);
        $fileName  = strtr($namespace, ["\\" => "/"]) . "/";
    }
    $fileName .= strtr($className, ["_" => "/"]) . ".php";

    require __DIR__ . "/src/" . $fileName;
}
