<?php
/**
 * Scabbia2 PHP Framework
 * https://github.com/eserozvataf/scabbia2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/eserozvataf/scabbia2 for the canonical source repository
 * @copyright   2010-2016 Eser Ozvataf. (http://eser.ozvataf.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

// MD # bootstrap sequence
// MD - determine the environment parameters
$tBasePath = dirname(__DIR__);
$tGmDate = gmdate("Y_m_d");

// MD - error reporting
error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", true);
ini_set("error_log", "{$tBasePath}/var/logs/error_{$tGmDate}.log");

set_error_handler(function ($uCode, $uMessage, $uFile, $uLine) {
    if ((error_reporting() & $uCode) !== 0) {
        throw new \ErrorException($uMessage, $uCode, 0, $uFile, $uLine);
    }

    return true;
});

// MD - instantiate and register the loader
$tLoader = require "{$tBasePath}/vendor/autoload.php";

return new \Scabbia\Framework\Project($tLoader);
