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
 *
 * -------------------------
 * Portions of this code are from Symfony YAML Component under the MIT license.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE-MIT
 * file that was distributed with this source code.
 *
 * Modifications made:
 * - Scabbia Framework code styles applied.
 * - All dump methods are moved under Dumper class.
 * - Redundant classes removed.
 * - Namespace changed.
 * - Tests ported to Scabbia2.
 * - Encoding checks removed.
 */

namespace Scabbia\Tests\Yaml;

use Scabbia\Testing\UnitTestFixture;
use Scabbia\Yaml\Dumper;
use Scabbia\Yaml\Inline;

/**
 * Tests of Inline class
 *
 * @package     Scabbia\Tests\Yaml
 * @since       2.0.0
 */
class InlineTest extends UnitTestFixture
{
    /**
     * Test method for Inline::parse
     *
     * @return void
     */
    public function testParse()
    {
        foreach ($this->getTestsForParse() as $yaml => $value) {
            $this->assertSame(
                $value,
                Inline::parse($yaml),
                sprintf("::parse() converts an inline YAML to a PHP structure (%s)", $yaml)
            );
        }
    }

    /**
     * Test method for Dumper::dumpInline
     *
     * @return void
     */
    public function testDump()
    {
        $testsForDump = $this->getTestsForDump();

        foreach ($testsForDump as $yaml => $value) {
            $this->assertEquals(
                $yaml,
                Dumper::dumpInline($value),
                sprintf("::dump() converts a PHP structure to an inline YAML (%s)", $yaml)
            );
        }

        foreach ($this->getTestsForParse() as $value) {
            $this->assertEquals($value, Inline::parse(Dumper::dumpInline($value)), "check consistency");
        }

        foreach ($testsForDump as $value) {
            $this->assertEquals($value, Inline::parse(Dumper::dumpInline($value)), "check consistency");
        }
    }

    /**
     * Test method for dump of numeric values w/ locale
     *
     * @return void
     */
    public function testDumpNumericValueWithLocale()
    {
        $locale = setlocale(LC_NUMERIC, 0);
        if ($locale === false) {
            $this->markTestSkipped("Your platform does not support locales.");
        }

        $required_locales = ["fr_FR.UTF-8", "fr_FR.UTF8", "fr_FR.utf-8", "fr_FR.utf8", "French_France.1252"];
        if (setlocale(LC_ALL, $required_locales) === false) {
            $this->markTestSkipped("Could not set any of required locales: " . implode(", ", $required_locales));
        }

        $this->assertEquals("1.2", Dumper::dumpInline(1.2));
        $this->assertContains(setlocale(LC_NUMERIC, 0), $required_locales);

        setlocale(LC_ALL, $locale);
    }

    /**
     * Test method for dump of exponential numerics
     *
     * @return void
     */
    public function testHashStringsResemblingExponentialNumericsShouldNotBeChangedToINF()
    {
        $value = "686e444";

        $this->assertSame($value, Inline::parse(Dumper::dumpInline($value)));
    }

    /**
     * Test method for incorrect quoted strings
     *
     * @return void
     */
    public function testParseScalarWithIncorrectlyQuotedStringShouldThrowException()
    {
        $this->expectException("Scabbia\\Yaml\\ParseException");

        $value = "'don't do somthin' like that'";
        Inline::parse($value);
    }

    /**
     * Test method for incorrect double quoted string
     *
     * @return void
     */
    public function testParseScalarWithIncorrectlyDoubleQuotedStringShouldThrowException()
    {
        $this->expectException("Scabbia\\Yaml\\ParseException");

        $value = "\"don\"t do somthin\" like that\"";
        Inline::parse($value);
    }

    /**
     * Test method for invalid mapping key
     *
     * @return void
     */
    public function testParseInvalidMappingKeyShouldThrowException()
    {
        $this->expectException("Scabbia\\Yaml\\ParseException");

        $value = "{ \"foo \" bar\": \"bar\" }";
        Inline::parse($value);
    }

    /**
     * Test method for invalid mapping
     *
     * @return void
     */
    public function testParseInvalidMappingShouldThrowException()
    {
        $this->expectException("Scabbia\\Yaml\\ParseException");

        Inline::parse("[foo] bar");
    }

    /**
     * Test method for invalid sequence
     *
     * @return void
     */
    public function testParseInvalidSequenceShouldThrowException()
    {
        $this->expectException("Scabbia\\Yaml\\ParseException");

        Inline::parse("{ foo: bar } bar");
    }

    /**
     * Test method for scalar with correct quoted string
     *
     * @return void
     */
    public function testParseScalarWithCorrectlyQuotedStringShouldReturnString()
    {
        $value = "'don''t do somthin'' like that'";
        $expect = "don't do somthin' like that";

        $this->assertSame($expect, Inline::parseScalar($value));
    }

    /**
     * Set of test cases for parser
     *
     * @return array
     */
    protected function getTestsForParse()
    {
        return [
            "" => "",
            "null" => null,
            "false" => false,
            "true" => true,
            "12" => 12,
            "-12" => -12,
            "\"quoted string\"" => "quoted string",
            "'quoted string'" => "quoted string",
            "12.30e+02" => 12.30e+02,
            "0x4D2" => 0x4D2,
            "02333" => 02333,
            ".Inf" => -log(0),
            "-.Inf" => log(0),
            "'686e444'" => "686e444",
            "686e444" => 646e444,
            "123456789123456789123456789123456789" => "123456789123456789123456789123456789",
            "\"foo\\r\\nbar\"" => "foo\r\nbar",
            "'foo#bar'" => "foo#bar",
            "'foo # bar'" => "foo # bar",
            "'#cfcfcf'" => "#cfcfcf",
            "::form_base.html.twig" => "::form_base.html.twig",

            "2007-10-30" => mktime(0, 0, 0, 10, 30, 2007),
            "2007-10-30T02:59:43Z" => gmmktime(2, 59, 43, 10, 30, 2007),
            "2007-10-30 02:59:43 Z" => gmmktime(2, 59, 43, 10, 30, 2007),
            "1960-10-30 02:59:43 Z" => gmmktime(2, 59, 43, 10, 30, 1960),
            "1730-10-30T02:59:43Z" => gmmktime(2, 59, 43, 10, 30, 1730),

            "\"a \\\"string\\\" with 'quoted strings inside'\"" => "a \"string\" with 'quoted strings inside'",
            "'a \"string\" with ''quoted strings inside'''" => "a \"string\" with 'quoted strings inside'",

            "'-dash'" => "-dash",
            "'-'" => "-",

            // sequences
            // urls are no key value mapping. see #3609. Valid yaml "key: value" mappings require a space after the
            // colon
            "[foo, http://urls.are/no/mappings, false, null, 12]" => [
                "foo",
                "http://urls.are/no/mappings",
                false,
                null,
                12
            ],
            "[  foo  ,   bar , false  ,  null     ,  12  ]" => ["foo", "bar", false, null, 12],
            "['foo,bar', 'foo bar']" => ["foo,bar", "foo bar"],

            // mappings
            "{foo:bar,bar:foo,false:false,null:null,integer:12}" => [
                "foo" => "bar",
                "bar" => "foo",
                "false" => false,
                "null" => null,
                "integer" => 12
            ],
            "{ foo  : bar, bar : foo,  false  :   false,  null  :   null,  integer :  12  }" => [
                "foo" => "bar",
                "bar" => "foo",
                "false" => false,
                "null" => null,
                "integer" => 12
            ],
            "{foo: 'bar', bar: 'foo: bar'}" => ["foo" => "bar", "bar" => "foo: bar"],
            "{'foo': 'bar', \"bar\": 'foo: bar'}" => ["foo" => "bar", "bar" => "foo: bar"],
            "{'foo''': 'bar', \"bar\\\"\": 'foo: bar'}" => ["foo'" => "bar", "bar\"" => "foo: bar"],
            "{'foo: ': 'bar', \"bar: \": 'foo: bar'}" => ["foo: " => 'bar', "bar: " => "foo: bar"],

            // nested sequences and mappings
            "[foo, [bar, foo]]" => ["foo", ["bar", "foo"]],
            "[foo, {bar: foo}]" => ["foo", ["bar" => "foo"]],
            "{ foo: {bar: foo} }" => ["foo" => ["bar" => "foo"]],
            "{ foo: [bar, foo] }" => ["foo" => ["bar", "foo"]],

            "[  foo, [  bar, foo  ]  ]" => ["foo", ["bar", "foo"]],

            "[{ foo: {bar: foo} }]" => [["foo" => ["bar" => "foo"]]],

            "[foo, [bar, [foo, [bar, foo]], foo]]" => ["foo", ["bar", ["foo", ["bar", "foo"]], "foo"]],

            "[foo, {bar: foo, foo: [foo, {bar: foo}]}, [foo, {bar: foo}]]" => [
                "foo",
                ["bar" => "foo", "foo" => ["foo", ["bar" => "foo"]]],
                ["foo", ["bar" => "foo"]]
            ],

            "[foo, bar: { foo: bar }]" => ["foo", "1" => ["bar" => ["foo" => "bar"]]],
            "[foo, '@foo.baz', { '%foo%': 'foo is %foo%', bar: '%foo%' }, true, '@service_container']" => [
                "foo",
                "@foo.baz",
                ["%foo%" => "foo is %foo%", "bar" => "%foo%"],
                true,
                "@service_container"
            ]
        ];
    }

    /**
     * Set of test cases for dumper
     *
     * @return array
     */
    protected function getTestsForDump()
    {
        return [
            "null" => null,
            "false" => false,
            "true" => true,
            "12" => 12,
            "'quoted string'" => "quoted string",
            "12.30e+02" => 12.30e+02,
            "1234" => 0x4D2,
            "1243" => 02333,
            // ".Inf" => -log(0),
            "-.Inf" => log(0),
            "'686e444'" => "686e444",
            "\"foo\\r\\nbar\"" => "foo\r\nbar",
            "'foo#bar'" => "foo#bar",
            "'foo # bar'" => "foo # bar",
            "'#cfcfcf'" => "#cfcfcf",

            "'a \"string\" with ''quoted strings inside'''" => "a \"string\" with 'quoted strings inside'",

            // sequences
            "[foo, bar, false, null, 12]" => ["foo", "bar", false, null, 12],
            "['foo,bar', 'foo bar']" => ["foo,bar", "foo bar"],

            // mappings
            "{ foo: bar, bar: foo, 'false': false, 'null': null, integer: 12 }" => [
                "foo" => "bar",
                "bar" => "foo",
                "false" => false,
                "null" => null,
                "integer" => 12
            ],
            "{ foo: bar, bar: 'foo: bar' }" => ["foo" => "bar", "bar" => "foo: bar"],

            // nested sequences and mappings
            "[foo, [bar, foo]]" => ["foo", ["bar", "foo"]],

            "[foo, [bar, [foo, [bar, foo]], foo]]" => ["foo", ["bar", ["foo", ["bar", "foo"]], "foo"]],

            "{ foo: { bar: foo } }" => ["foo" => ["bar" => "foo"]],

            "[foo, { bar: foo }]" => ["foo", ["bar" => "foo"]],

            "[foo, { bar: foo, foo: [foo, { bar: foo }] }, [foo, { bar: foo }]]" => [
                "foo",
                ["bar" => "foo", "foo" => ["foo", ["bar" => "foo"]]], ["foo", ["bar" => "foo"]]
            ],

            "[foo, '@foo.baz', { '%foo%': 'foo is %foo%', bar: '%foo%' }, true, '@service_container']" => [
                "foo",
                "@foo.baz",
                ["%foo%" => "foo is %foo%", "bar" => "%foo%"],
                true,
                "@service_container"
            ]
        ];
    }
}
