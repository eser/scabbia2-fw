<?php

require __DIR__ . "/src/Scabbia/Unittests/TestFixture.php";

/**
 * Executes tests.
 *
 * @param array $uTests The set of tests going to be tested.
 */
function doTests(array $uTests)
{
    foreach ($uTests as $tTest) {
        echo "<h2>{$tTest}</h2>";

        include __DIR__ . "/tests/{$tTest}.php";

        $instance = new $tTest ();
        $instance->test();
        $instance->exportHtml();
    }
}

/**
 * @var array Set of test names.
 */
$tTests = [
    'SampleTest'
];

echo "<h1>Unittests</h1>";
doTests($tTests);
