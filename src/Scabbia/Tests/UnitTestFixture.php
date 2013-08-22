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

namespace Scabbia\Tests;

/**
 * Scabbia\Tests: UnitTestFixture Class
 *
 * A small unit test implementation which helps us during the development of
 * Scabbia2 PHP Framework's itself and related production code.
 *
 * @author Eser Ozvataf <eser@sent.com>
 */
abstract class UnitTestFixture
{
    /**
     * @var bool Indicates test fixture is failed or not.
     */
    public $isFailed = false;
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
     * Begin testing all methods of the fixture.
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
     * Tests the specified method of the fixture.
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

        if ($uIsFailed) {
            $this->isFailed = true;
        }
    }

    /**
     * SetUp method of the fixture.
     *
     * This method is being executed when the test is started.
     */
    protected function setUp()
    {

    }

    /**
     * TearDown method of the fixture.
     *
     * This method is being executed when the test is finished.
     */
    protected function tearDown()
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
     * Tests if given variable derived from given class.
     *
     * @param $uVariable    mixed   The variable
     * @param $uClassName   mixed   Class name
     * @param $uMessage     mixed   Message (optional)
     */
    public function assertInstanceOf($uVariable, $uClassName, $uMessage = null)
    {
        $this->testAddReport("assertInstanceOf", $uVariable instanceof $uClassName, $uMessage);
    }

    /**
     * Tests if given variables have the same type and value.
     *
     * @param $uVariable1   mixed   First variable
     * @param $uVariable2   mixed   Second variable
     * @param $uMessage     mixed   Message (optional)
     */
    public function assertSame($uVariable1, $uVariable2, $uMessage = null)
    {
        $this->testAddReport("assertSame", $uVariable1 !== $uVariable2, $uMessage);
    }

    /**
     * Tests if given variables have the same value.
     *
     * @param $uVariable1   mixed   First variable
     * @param $uVariable2   mixed   Second variable
     * @param $uMessage     mixed   Message (optional)
     */
    public function assertEquals($uVariable1, $uVariable2, $uMessage = null)
    {
        $this->testAddReport("assertEquals", $uVariable1 != $uVariable2, $uMessage);
    }

    /**
     * Tests if given variable is a substring of another given variable.
     *
     * @param $uVariable1   mixed   First variable
     * @param $uVariable2   mixed   Second variable
     * @param $uMessage     mixed   Message (optional)
     */
    public function assertContains($uVariable1, $uVariable2, $uMessage = null)
    {
        if (is_scalar($uVariable2)) {
            $this->testAddReport("assertContains", strpos($uVariable1, $uVariable2) === false, $uMessage);
            return;
        }

        $this->testAddReport("assertContains", !in_array($uVariable1, $uVariable2, true), $uMessage);
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
}
