<?php
/**
 * Scabbia2 PHP Framework
 * http://www.scabbiafw.com/
 *
 * Licensed under the Apache License, Version 2.0
 *
 * @link        http://github.com/scabbiafw/scabbia2 for the canonical source repository
 * @copyright   Copyright (c) 2010-2013 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

require __DIR__ . "/src/Scabbia/Tests/UnitTestFixture.php";
require __DIR__ . "/src/Scabbia/Tests/IOutput.php";
require __DIR__ . "/src/Scabbia/Tests/HtmlOutput.php";
require __DIR__ . "/src/Scabbia/Tests/ConsoleOutput.php";

$tTests = [
    "ScabbiaTests\\SampleTest"
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

    if (($tPos = strrpos($tTestClass, '\\')) !== false) {
        $uTestFile = substr($tTestClass, $tPos + 1);
    } else {
        $uTestFile = $tTestClass;
    }

    include __DIR__ . "/tests/{$uTestFile}.php";

    $instance = new $tTestClass ();
    $instance->test();

    if ($instance->isFailed) {
        $tIsEverFailed = true;
    }

    $tOutput->export($instance->testReport);
}

exit($tIsEverFailed ? 1 : 0);
