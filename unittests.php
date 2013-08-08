<?php
/**
 * Scabbia2 PHP Framework Version 0.1
 * http://www.scabbiafw.com/
 * Licensed under the Apache License, Version 2.0
 */

require __DIR__ . "/src/Scabbia/Unittests/TestFixture.php";
require __DIR__ . "/src/Scabbia/Unittests/IOutput.php";
require __DIR__ . "/src/Scabbia/Unittests/HtmlOutput.php";
require __DIR__ . "/src/Scabbia/Unittests/ConsoleOutput.php";

$tTests = [
    "ScabbiaTests\\SampleTest"
];

if (PHP_SAPI === "cli") {
    $tOutput = new Scabbia\Unittests\ConsoleOutput();
} else {
    $tOutput = new Scabbia\Unittests\HtmlOutput();
}

$tIsEverFailed = false;

$tOutput->writeHeader(1, "Unittests");

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

    $tOutput->export($instance);
}

exit($tIsEverFailed ? 1 : 0);
