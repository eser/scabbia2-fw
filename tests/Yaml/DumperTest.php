<?php
/**
 * Scabbia2 PHP Framework Code
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        http://github.com/scabbiafw/scabbia2-fw for the canonical source repository
 * @copyright   2010-2015 Scabbia Framework Organization. (http://www.scabbiafw.com/)
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
use Scabbia\Yaml\Parser;
use Scabbia\Tests\Yaml\DummyClass;

/**
 * Tests of Dumper class
 *
 * @package     Scabbia\Tests\Yaml
 * @since       2.0.0
 */
class DumperTest extends UnitTestFixture
{
    /** @type Scabbia\Yaml\Parser $parser */
    protected $parser;
    /** @type Scabbia\Yaml\Dumper $dumper */
    protected $dumper;
    protected $path;

    protected $array = [
        "" => "bar",
        "foo" => "#bar",
        "foo'bar" => [],
        "bar" => [1, "foo"],
        "foobar" => [
            "foo" => "bar",
            "bar" => [1, "foo"],
            "foobar" => [
                "foo" => "bar",
                "bar" => [1, "foo"],
            ],
        ],
    ];

    /**
     * Test fixture setup method
     *
     * @return void
     */
    protected function setUp()
    {
        $this->parser = new Parser();
        $this->dumper = new Dumper();
        $this->path = __DIR__ . "/Fixtures";
    }

    /**
     * Test fixture teardown method
     *
     * @return void
     */
    protected function tearDown()
    {
        $this->parser = null;
        $this->dumper = null;
        $this->path = null;
        $this->array = null;
    }

    public function testSetIndentation()
    {
        $this->dumper->setIndentation(7);

        $expected = <<<EOF
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
       - 1
       - foo
foobar:
       foo: bar
       bar:
              - 1
              - foo
       foobar:
              foo: bar
              bar:
                     - 1
                     - foo

EOF;
        $this->assertEquals($expected, $this->dumper->dump($this->array, 4, 0));
    }

    public function testSpecifications()
    {
        $files = $this->parser->parse(file_get_contents($this->path . "/index.yml"));
        foreach ($files as $file) {
            $yamls = file_get_contents($this->path . "/{$file}.yml");

            // split YAMLs documents
            foreach (preg_split("/^---( %YAML\:1\.0)?/m", $yamls) as $yaml) {
                if (!$yaml) {
                    continue;
                }

                $test = $this->parser->parse($yaml);
                if (isset($test["dump_skip"]) && $test["dump_skip"]) {
                    continue;
                } elseif (isset($test["todo"]) && $test["todo"]) {
                    // TODO
                } else {
                    eval("$expected = " . trim($test["php"]) . ";");
                    $this->assertSame(
                        $expected,
                        $this->parser->parse($this->dumper->dump($expected, 10)),
                        $test["test"]
                    );
                }
            }
        }
    }

    public function testInlineLevel()
    {
        $expected = <<<EOF
{
    '': bar,
    foo: '#bar',
    'foo''bar': {  },
    bar: [1, foo],
    foobar: { foo: bar, bar: [1, foo], foobar: { foo: bar, bar: [1, foo] } }
}
EOF;
        $this->assertEquals(
            $expected,
            $this->dumper->dump($this->array, -10),
            "->dump() takes an inline level argument"
        );

        $this->assertEquals(
            $expected,
            $this->dumper->dump($this->array, 0),
            "->dump() takes an inline level argument"
        );

        $expected = <<<EOF
'': bar
foo: '#bar'
'foo''bar': {  }
bar: [1, foo]
foobar: { foo: bar, bar: [1, foo], foobar: { foo: bar, bar: [1, foo] } }

EOF;
        $this->assertEquals(
            $expected,
            $this->dumper->dump($this->array, 1),
            "->dump() takes an inline level argument"
        );

        $expected = <<<EOF
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar: [1, foo]
    foobar: { foo: bar, bar: [1, foo] }

EOF;
        $this->assertEquals($expected, $this->dumper->dump($this->array, 2), "->dump() takes an inline level argument");

        $expected = <<<EOF
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar:
        - 1
        - foo
    foobar:
        foo: bar
        bar: [1, foo]

EOF;
        $this->assertEquals($expected, $this->dumper->dump($this->array, 3), "->dump() takes an inline level argument");

        $expected = <<<EOF
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar:
        - 1
        - foo
    foobar:
        foo: bar
        bar:
            - 1
            - foo

EOF;
        $this->assertEquals(
            $expected,
            $this->dumper->dump($this->array, 4),
            "->dump() takes an inline level argument"
        );

        $this->assertEquals(
            $expected,
            $this->dumper->dump($this->array, 10),
            "->dump() takes an inline level argument"
        );
    }

    public function testObjectSupport()
    {
        $dump = $this->dumper->dump(["foo" => new DummyClass(), "bar" => 1], 0, 0, false, true);

        $this->assertEquals(
            "{ foo: !!php/object:O:30:\"Scabbia\Tests\Yaml\DummyClass\":1:{s:1:\"b\";s:3:\"foo\";}, bar: 1 }",
            $dump,
            "->dump() is able to dump objects"
        );
    }

    /**
     * @dataProvider getEscapeSequences
     */
    public function testEscapedEscapeSequencesInQuotedScalar($input, $expected)
    {
        $this->assertEquals($expected, $this->dumper->dump($input));
    }

    public function getEscapeSequences()
    {
        return [
            "null" => ["\t\\0", "\"\t\\\\0\""],
            "bell" => ["\t\\a", "\"\t\\\\a\""],
            "backspace" => ["\t\\b", "\"\t\\\\b\""],
            "horizontal-tab" => ["\t\\t", "\"\t\\\\t\""],
            "line-feed" => ["\t\\n", "\"\t\\\\n\""],
            "vertical-tab" => ["\t\\v", "\"\t\\\\v\""],
            "form-feed" => ["\t\\f", "\"\t\\\\f\""],
            "carriage-return" => ["\t\\r", "\"\t\\\\r\""],
            "escape" => ["\t\\e", "\"\t\\\\e\""],
            "space" => ["\t\\ ", "\"\t\\\\ \""],
            "double-quote" => ["\t\\\"", "\"\t\\\\\\\"\""],
            "slash" => ["\t\\/", "\"\t\\\\/\""],
            "backslash" => ["\t\\\\", "\"\t\\\\\\\\\""],
            "next-line" => ["\t\\N", "\"\t\\\\N\""],
            "non-breaking-space" => ["\t\\�", "\"\t\\\\�\""],
            "line-separator" => ["\t\\L", "\"\t\\\\L\""],
            "paragraph-separator" => ["\t\\P", "\"\t\\\\P\""],
        ];
    }
}
