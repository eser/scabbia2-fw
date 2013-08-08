<?php
/**
 * Scabbia2 PHP Framework Version 0.1
 * http://www.scabbiafw.com/
 * Licensed under the Apache License, Version 2.0
 */

namespace Scabbia\Unittests;

/**
 * Scabbia\Unittests: TestFixture Class
 *
 * A small unittest implementation which helps us during the development of
 * Scabbia2 PHP Framework's itself and related production code.
 *
 * @package Scabbia
 * @subpackage Unittests
 * @version 0.1
 */
abstract class TestFixture
{
    /**
     * @var array Track of the unit which is currently testing.
     */
    public $testStack = [];
    /**
     * @var array Output of test results.
     */
    public $testReport = [];
    /**
     * @var mixed The set of outcomes which is going to be tested.
     */
    public $testExpectations;


    /**
     * Begin testing all methods of TestFixture.
     */
    public function test()
    {
        $tMe = new \ReflectionClass($this);
        $tMethods = $tMe->getMethods(\ReflectionMethod::IS_PUBLIC);

        $tReservedMethods = ["setUp", "tearDown"];

        foreach ($tMethods as $tMethod) {
            if ($tMethod->class !== $tMe->name || in_array($tMethod->name, $tReservedMethods)) {
                continue;
            }

            $this->testUnit("{$tMe->name}->{$tMethod->name}()", [&$this, $tMethod->name]);
        }
    }

    /**
     * Tests the specified method of TestFixture.
     *
     * @param $uName        string      Name of the method
     * @param $uCallback    callable    Target method
     */
    public function testUnit($uName, callable $uCallback)
    {
        $this->testStack[] = ["name" => $uName, "callback" => $uCallback];

        $tException = null;

        $this->testExpectations = [
            "ignore" => [],
            "expect" => []
        ];
        $this->setUp();
        try {
            call_user_func($uCallback);
        } catch (\Exception $ex) {
            $tException = $ex;
        }
        $this->tearDown();

        if ($tException !== null) {
            foreach ($this->testExpectations["ignore"] as $tExpectation) {
                if (!is_a($tException, $tExpectation)) {
                    continue;
                }

                $this->testAddReport(
                    "ignoreException",
                    false,
                    get_class($tException) . ": " . $tException->getMessage()
                );
                $tException = null;
                break;
            }
        }

        $tExpectations = $this->testExpectations["expect"];
        foreach ($tExpectations as $tExpectationKey => $tExpectation) {
            if ($tException !== null && is_a($tException, $tExpectation)) {
                unset($tExpectations[$tExpectationKey]);
                $this->testAddReport(
                    "expectException",
                    false,
                    get_class($tException) . ": " . $tException->getMessage()
                );
                $tException = null;
            }
        }

        foreach ($tExpectations as $tExpectation) {
            $this->testAddReport("expectException", true, $tExpectation);
        }

        if ($tException !== null) {
            $this->testAddReport("exception", true, get_class($tException) . ": " . $tException->getMessage());
        }

        array_pop($this->testStack);
    }

    /**
     * Adds test output to the final report.
     *
     * @param $uOperation   string      Name of the operation
     * @param $uIsFailed    bool        Is test failed or not?
     * @param $uMessage     mixed       Message (optional)
     */
    public function testAddReport($uOperation, $uIsFailed, $uMessage = null)
    {
        $tScope = end($this->testStack);

        if (!isset($this->testReport[$tScope["name"]])) {
            $this->testReport[$tScope["name"]] = [];
        }

        $this->testReport[$tScope["name"]][] = [
            "operation" => $uOperation,
            "failed" => $uIsFailed,
            "message" => $uMessage
        ];
    }

    /**
     * SetUp method of TestFixture.
     *
     * This method is being executed when the test is started.
     */
    public function setUp()
    {

    }

    /**
     * TearDown method of TestFixture.
     *
     * This method is being executed when the test is finished.
     */
    public function tearDown()
    {

    }

    /**
     * Tests if given condition is **not** true.
     *
     * @param $uCondition   bool    The condition
     * @param $uMessage     mixed   Message (optional)
     */
    public function assertTrue($uCondition, $uMessage = null)
    {
        $this->testAddReport("assertTrue", $uCondition, $uMessage);
    }

    /**
     * Tests if given condition is **not** false.
     *
     * @param $uCondition   bool    The condition
     * @param $uMessage     mixed   Message (optional)
     */
    public function assertFalse($uCondition, $uMessage = null)
    {
        $this->testAddReport("assertFalse", !$uCondition, $uMessage);
    }

    /**
     * Tests if given condition is **not** null.
     *
     * @param $uVariable    bool    The condition
     * @param $uMessage     mixed   Message (optional)
     */
    public function assertNull($uVariable, $uMessage = null)
    {
        $this->testAddReport("assertNull", $uVariable === null, $uMessage);
    }

    /**
     * Tests if given condition is null.
     *
     * @param $uVariable    bool    The condition
     * @param $uMessage     mixed   Message (optional)
     */
    public function assertNotNull($uVariable, $uMessage = null)
    {
        $this->testAddReport("assertNotNull", $uVariable !== null, $uMessage);
    }

    /**
     * Tests if will testing unit throw specified exception or not.
     *
     * @param $uExceptionType    string     Name of the exception type
     */
    public function expectException($uExceptionType)
    {
        $this->testExpectations["expect"][] = $uExceptionType;
    }

    /**
     * Ignores if testing unit throws specified exception during test.
     *
     * @param $uExceptionType    string     Name of the exception type
     */
    public function ignoreException($uExceptionType)
    {
        $this->testExpectations["ignore"][] = $uExceptionType;
    }

    /**
     * Outputs the report in var_dump representation.
     */
    public function export()
    {
        var_dump($this->testReport);
    }

    /**
     * Outputs the report in HTML representation.
     */
    public function exportHtml()
    {
        foreach ($this->testReport as $tEntryKey => $tEntry) {
            echo "<p>";
            echo "<strong>{$tEntryKey}:</strong><br />";
            echo "<ul>";

            $tPassed = true;
            foreach ($tEntry as $tTest) {
                if ($tTest['failed']) {
                    $tPassed = false;
                    echo "<li>";
                    echo "<span style=\"color: red;\">{$tTest['operation']}</span>";
                    if ($tTest['message'] !== null) {
                        echo ": {$tTest['message']}";
                    }
                    echo "</li>";
                } else {
                    echo "<li>";
                    echo "<span style=\"color: green;\">{$tTest['operation']}</span>";
                    if ($tTest['message'] !== null) {
                        echo ": {$tTest['message']}";
                    }
                    echo "</li>";
                }
            }

            echo "</ul>";

            if (!$tPassed) {
                echo "<span style=\"color: red;\">FAILED</span>";
            } else {
                echo "<span style=\"color: green;\">PASSED</span>";
            }

            echo "</p>";
        }
    }
}
