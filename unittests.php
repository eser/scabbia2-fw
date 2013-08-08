<?php

require __DIR__ . "/src/Scabbia/Unittests/TestFixture.php";
require __DIR__ . "/src/Scabbia/Unittests/IOutput.php";
require __DIR__ . "/src/Scabbia/Unittests/HtmlOutput.php";
require __DIR__ . "/src/Scabbia/Unittests/ConsoleOutput.php";

/**
 * Executes tests.
 *
 * @param array $uTests The set of tests going to be tested.
 */
function doTests(array $uTests)
{
    if (PHP_SAPI === "cli") {
        $tOutput = new Scabbia\Unittests\ConsoleOutput();
    } else {
        $tOutput = new Scabbia\Unittests\HtmlOutput();
    }

    $tIsEverFailed = false;

    $tOutput->writeHeader(1, "Unittests");

    foreach ($uTests as $tTest) {
        $tOutput->writeHeader(2, $tTest);

        include __DIR__ . "/tests/{$tTest}.php";

        $instance = new $tTest ();
        $instance->test();

        if ($instance->isFailed) {
            $tIsEverFailed = true;
        }

        $tOutput->export($instance);
    }

    exit($tIsEverFailed ? 1 : 0);
}

/**
 * @var array Set of test names.
 */
$tTests = [
    'SampleTest'
];

doTests($tTests);
