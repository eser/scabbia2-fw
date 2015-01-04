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
use Scabbia\Yaml\Parser;
use Scabbia\Tests\Yaml\DummyClass;

/**
 * Tests of Parser class
 *
 * @package     Scabbia\Tests\Yaml
 * @since       2.0.0
 */
class ParserTest extends UnitTestFixture
{
    /** @type Scabbia\Yaml\Parser $parser */
    protected $parser;


    /**
     * Test fixture setup method
     *
     * @return void
     */
    protected function setUp()
    {
        $this->parser = new Parser();
    }

    /**
     * Test fixture teardown method
     *
     * @return void
     */
    protected function tearDown()
    {
        $this->parser = null;
    }

    /**
     * Gets data form specifications
     *
     * @return array
     */
    public function getDataFormSpecifications()
    {
        $parser = new Parser();
        $path = __DIR__ . "/Fixtures";

        $tests = [];
        $files = $parser->parse(file_get_contents($path . "/index.yml"));
        foreach ($files as $file) {
            $yamls = file_get_contents($path . "/" . $file . ".yml");

            // split YAMLs documents
            foreach (preg_split("/^---( %YAML\\:1\\.0)?/m", $yamls) as $yaml) {
                if (!$yaml) {
                    continue;
                }

                $test = $parser->parse($yaml);
                if (isset($test["todo"]) && $test["todo"]) {
                    // TODO
                } else {
                    $expected = var_export(eval("return " . trim($test["php"]) . ";"), true);

                    $tests[] = [$file, $expected, $test["yaml"], $test["test"]];
                }
            }
        }

        return $tests;
    }

    /**
     * Tests tabs in yaml
     *
     * @return void
     */
    public function testTabsInYaml()
    {
        // test tabs in YAML
        $yamls = [
            "foo:\n\tbar",
            "foo:\n \tbar",
            "foo:\n\t bar",
            "foo:\n \t bar",
        ];

        foreach ($yamls as $yaml) {
            try {
                $this->parser->parse($yaml);

                $this->fail("YAML files must not contain tabs");
            } catch (\Exception $e) {
                $this->assertInstanceOf("\\Exception", $e, "YAML files must not contain tabs");
                $this->assertEquals(
                    "A YAML file cannot contain tabs as indentation at line 2 (near \"" . strpbrk($yaml, "\t") . "\").",
                    $e->getMessage(),
                    "YAML files must not contain tabs"
                );
            }
        }
    }

    /**
     * Tests end of the document marker
     *
     * @return void
     */
    public function testEndOfTheDocumentMarker()
    {
        $yaml = <<<EOF
--- %YAML:1.0
foo
...
EOF;

        $this->assertEquals("foo", $this->parser->parse($yaml));
    }

    /**
     * Gets block chomping tests
     *
     * @return array
     */
    public function getBlockChompingTests()
    {
        $tests = [];

        $yaml = <<<'EOF'
foo: |-
    one
    two
bar: |-
    one
    two

EOF;
        $expected = [
            "foo" => "one\ntwo",
            "bar" => "one\ntwo",
        ];
        $tests["Literal block chomping strip with single trailing newline"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: |-
    one
    two

bar: |-
    one
    two


EOF;
        $expected = [
            "foo" => "one\ntwo",
            "bar" => "one\ntwo",
        ];
        $tests["Literal block chomping strip with multiple trailing newlines"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: |-
    one
    two
bar: |-
    one
    two
EOF;
        $expected = [
            "foo" => "one\ntwo",
            "bar" => "one\ntwo",
        ];
        $tests["Literal block chomping strip without trailing newline"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: |
    one
    two
bar: |
    one
    two

EOF;
        $expected = [
            "foo" => "one\ntwo\n",
            "bar" => "one\ntwo\n",
        ];
        $tests["Literal block chomping clip with single trailing newline"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: |
    one
    two

bar: |
    one
    two


EOF;
        $expected = [
            "foo" => "one\ntwo\n",
            "bar" => "one\ntwo\n",
        ];
        $tests["Literal block chomping clip with multiple trailing newlines"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: |
    one
    two
bar: |
    one
    two
EOF;
        $expected = [
            "foo" => "one\ntwo\n",
            "bar" => "one\ntwo",
        ];
        $tests["Literal block chomping clip without trailing newline"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: |+
    one
    two
bar: |+
    one
    two

EOF;
        $expected = [
            "foo" => "one\ntwo\n",
            "bar" => "one\ntwo\n",
        ];
        $tests["Literal block chomping keep with single trailing newline"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: |+
    one
    two

bar: |+
    one
    two


EOF;
        $expected = [
            "foo" => "one\ntwo\n\n",
            "bar" => "one\ntwo\n\n",
        ];
        $tests["Literal block chomping keep with multiple trailing newlines"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: |+
    one
    two
bar: |+
    one
    two
EOF;
        $expected = [
            "foo" => "one\ntwo\n",
            "bar" => "one\ntwo",
        ];
        $tests["Literal block chomping keep without trailing newline"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: >-
    one
    two
bar: >-
    one
    two

EOF;
        $expected = [
            "foo" => "one two",
            "bar" => "one two",
        ];
        $tests["Folded block chomping strip with single trailing newline"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: >-
    one
    two

bar: >-
    one
    two


EOF;
        $expected = [
            "foo" => "one two",
            "bar" => "one two",
        ];
        $tests["Folded block chomping strip with multiple trailing newlines"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: >-
    one
    two
bar: >-
    one
    two
EOF;
        $expected = [
            "foo" => "one two",
            "bar" => "one two",
        ];
        $tests["Folded block chomping strip without trailing newline"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: >
    one
    two
bar: >
    one
    two

EOF;
        $expected = [
            "foo" => "one two\n",
            "bar" => "one two\n",
        ];
        $tests["Folded block chomping clip with single trailing newline"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: >
    one
    two

bar: >
    one
    two


EOF;
        $expected = [
            "foo" => "one two\n",
            "bar" => "one two\n",
        ];
        $tests["Folded block chomping clip with multiple trailing newlines"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: >
    one
    two
bar: >
    one
    two
EOF;
        $expected = [
            "foo" => "one two\n",
            "bar" => "one two",
        ];
        $tests["Folded block chomping clip without trailing newline"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: >+
    one
    two
bar: >+
    one
    two

EOF;
        $expected = [
            "foo" => "one two\n",
            "bar" => "one two\n",
        ];
        $tests["Folded block chomping keep with single trailing newline"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: >+
    one
    two

bar: >+
    one
    two


EOF;
        $expected = [
            "foo" => "one two\n\n",
            "bar" => "one two\n\n",
        ];
        $tests["Folded block chomping keep with multiple trailing newlines"] = [$expected, $yaml];

        $yaml = <<<'EOF'
foo: >+
    one
    two
bar: >+
    one
    two
EOF;
        $expected = [
            "foo" => "one two\n",
            "bar" => "one two",
        ];
        $tests["Folded block chomping keep without trailing newline"] = [$expected, $yaml];

        return $tests;
    }

    /**
     * Tests block literal with leading new lines
     * Regression test for issue #7989.
     *
     * @see https://github.com/symfony/symfony/issues/7989
     *
     * @return void
     */
    public function testBlockLiteralWithLeadingNewlines()
    {
        $yaml = <<<'EOF'
foo: |-


    bar

EOF;
        $expected = [
            "foo" => "\n\nbar"
        ];

        $this->assertSame($expected, $this->parser->parse($yaml));
    }

    /**
     * Tests object support
     *
     * @return void
     */
    public function testObjectSupport()
    {
        $input = <<<EOF
foo: !!php/object:O:29:"Scabbia\Tests\Yaml\DummyClass":1:{s:1:"b";s:3:"foo";}
bar: 1
EOF;
        $this->assertEquals(
            ["foo" => new DummyClass(), "bar" => 1],
            $this->parser->parse($input, false, true),
            "->parse() is able to parse objects"
        );
    }

    /**
     * Tests unindented collection exceptions
     *
     * @return void
     */
    public function testUnindentedCollectionException()
    {
        $this->expectException("Scabbia\\Yaml\\ParseException");

        $yaml = <<<EOF

collection:
-item1
-item2
-item3

EOF;

        $this->parser->parse($yaml);
    }

    /**
     * Tests multiple document exceptions
     *
     * @return void
     */
    public function testMultipleDocumentsNotSupportedException()
    {
        $this->expectException("Scabbia\\Yaml\\ParseException");

        $yaml = <<<EOF
# Ranking of 1998 home runs
---
- Mark McGwire
- Sammy Sosa
- Ken Griffey

# Team ranking
---
- Chicago Cubs
- St Louis Cardinals
EOF;

        $this->parser->parse($yaml);
    }

    /**
     * Tests sequence in a mapping
     *
     * @return void
     */
    public function testSequenceInAMapping()
    {
        $this->expectException("Scabbia\\Yaml\\ParseException");

        $yaml = <<<EOF
yaml:
  hash: me
  - array stuff
EOF;

        $this->parser->parse($yaml);
    }

    /**
     * Tests mapping in a sequence
     *
     * @return void
     */
    public function testMappingInASequence()
    {
        $this->expectException("Scabbia\\Yaml\\ParseException");

        $yaml = <<<EOF
yaml:
  - array stuff
  hash: me
EOF;

        $this->parser->parse($yaml);
    }

    /**
     * Tests empty value
     *
     * @return void
     */
    public function testEmptyValue()
    {
        $input = <<<EOF
hash:
EOF;

        $this->assertEquals(["hash" => null], $this->parser->parse($input));
    }

    /**
     * Tests string block with comments
     *
     * @return void
     */
    public function testStringBlockWithComments()
    {
        $yaml1 = <<<EOF
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOF;

        $yaml2 = <<<EOF
content: |
    # comment 1
    header

        # comment 2
        <body>
            <h1>title</h1>
        </body>

    footer # comment3
EOF;

        $this->assertEquals(["content" => $yaml1], $this->parser->parse($yaml2));
    }

    /**
     * Tests folded string block with comments
     *
     * @return void
     */
    public function testFoldedStringBlockWithComments()
    {
        $yaml1 = <<<EOF
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOF;

        $yaml2 = <<<EOF
-
    content: |
        # comment 1
        header

            # comment 2
            <body>
                <h1>title</h1>
            </body>

        footer # comment3
EOF;

        $this->assertEquals([["content" => $yaml1]], $this->parser->parse($yaml2));
    }

    public function testNestedFoldedStringBlockWithComments()
    {
        $yaml = <<<EOF
-
    title: some title
    content: |
        # comment 1
        header

            # comment 2
            <body>
                <h1>title</h1>
            </body>

        footer # comment3
EOF;

        $this->assertEquals(
            [[
                "title"   => "some title",
                "content" => <<<EOF
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOF
            ]],
            $this->parser->parse($yaml)
        );
    }

    public function testReferenceResolvingInInlineStrings()
    {
        $yaml = <<<EOF
var:  &var var-value
scalar: *var
list: [ *var ]
list_in_list: [[ *var ]]
map_in_list: [ { key: *var } ]
embedded_mapping: [ key: *var ]
map: { key: *var }
list_in_map: { key: [*var] }
map_in_map: { foo: { bar: *var } }
EOF;

        $this->assertEquals(
            [
                "var" => "var-value",
                "scalar" => "var-value",
                "list" => ["var-value"],
                "list_in_list" => [["var-value"]],
                "map_in_list" => [["key" => "var-value"]],
                "embedded_mapping" => [["key" => "var-value"]],
                "map" => ["key" => "var-value"],
                "list_in_map" => ["key" => ["var-value"]],
                "map_in_map" => ["foo" => ["bar" => "var-value"]],
            ],
            $this->parser->parse($yaml)
        );
    }
}
