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

// include the loader class
require __DIR__ . "/src/Loader/Loader.php";

// instantiate and register the loader
$tLoader = \Scabbia\Loader\Loader::init();

// register the base directories for the namespace prefix
$tLoader->addPsr4("Scabbia\\", __DIR__ . "/src/");
$tLoader->addPsr4("Scabbia\\Tests\\", __DIR__ . "/tests/");

use Scabbia\Interfaces\Console;
use Scabbia\Testing\TestsTask;

$tConfig = [
    "fixtures" => [
        "Scabbia\\Tests\\Yaml\\ParserTest",
        "Scabbia\\Tests\\Yaml\\InlineTest",
        "Scabbia\\Tests\\Security\\HashTest"
    ]
];

$tInterface = new Console();
$tTestTask = new TestsTask($tConfig, $tInterface);
$tExitCode = $tTestTask->executeTask([]);

exit($tExitCode);
