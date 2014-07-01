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

// include the loader class
require __DIR__ . "/src/Loaders/Psr4.php";

// instantiate and register the loader
$tLoader = new \Scabbia\Loaders\Psr4();
$tLoader->register();

// register the base directories for the namespace prefix
$tLoader->addNamespace("Scabbia\\", __DIR__ . "/src/");
$tLoader->addNamespace("Scabbia\\Tests\\", __DIR__ . "/tests/");

use Scabbia\Interfaces\Console;
use Scabbia\Testing\TestsCommand;

$tConfig = [
    "fixtures" => [
        "Scabbia\\Tests\\Yaml\\ParserTest",
        "Scabbia\\Tests\\Yaml\\InlineTest",
        "Scabbia\\Tests\\Security\\HashTest"
    ]
];

$tInterface = new Console();
$tTestCommand = new TestsCommand($tConfig, $tInterface);
$tExitCode = $tTestCommand->executeCommand([]);

exit($tExitCode);
