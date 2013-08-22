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

require __DIR__ . "/psr0autoloader.php";
spl_autoload_register('autoload');

$tTests = [
    "Scabbia\\Yaml\\Tests\\ParserTest",
    "Scabbia\\Yaml\\Tests\\InlineTest"
];

if (PHP_SAPI === "cli") {
    $tOutput = new Scabbia\Tests\ConsoleOutput();
} else {
    $tOutput = new Scabbia\Tests\HtmlOutput();
}

$tIsEverFailed = false;

$tOutput->writeHeader(1, "Unit Tests");

foreach ($tTests as $tTestClass) {
    $tOutput->writeHeader(2, $tTestClass);

    include __DIR__ . "/src/". strtr($tTestClass, ["\\" => "/"]) . ".php";

    $instance = new $tTestClass ();
    $instance->test();

    if ($instance->isFailed) {
        $tIsEverFailed = true;
    }

    $tOutput->export($instance->testReport);
}

exit($tIsEverFailed ? 1 : 0);
