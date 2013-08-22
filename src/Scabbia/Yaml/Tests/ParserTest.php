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
 *
 * -------------------------
 * Many portions of this file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
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

namespace Scabbia\Yaml\Tests;

use Scabbia\Tests\UnitTestFixture;
use Scabbia\Yaml\Parser;
use Scabbia\Yaml\Tests\DummyClass;

class ParserTest extends UnitTestFixture
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new Parser();
    }

    protected function tearDown()
    {
        $this->parser = null;
    }

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

    public function testTabsInYaml()
    {
        // test tabs in YAML
        $yamls = [
            "foo:\n	bar",
            "foo:\n 	bar",
            "foo:\n	 bar",
            "foo:\n 	 bar",
        ];

        foreach ($yamls as $yaml) {
            try {
                $content = $this->parser->parse($yaml);

                $this->fail("YAML files must not contain tabs");
            } catch (\Exception $e) {
                $this->assertInstanceOf("\\Exception", $e, "YAML files must not contain tabs");
                $this->assertEquals("A YAML file cannot contain tabs as indentation at line 2 (near \"" . strpbrk($yaml, "\t") . "\").", $e->getMessage(), "YAML files must not contain tabs");
            }
        }
    }

    public function testEndOfTheDocumentMarker()
    {
        $yaml = <<<EOF
--- %YAML:1.0
foo
...
EOF;

        $this->assertEquals("foo", $this->parser->parse($yaml));
    }

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
     * Regression test for issue #7989.
     *
     * @see https://github.com/symfony/symfony/issues/7989
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

    public function testObjectSupportEnabled()
    {
        $input = <<<EOF
foo: !!php/object:O:29:"Scabbia\Yaml\Tests\DummyClass":1:{s:1:"b";s:3:"foo";}
bar: 1
EOF;
        $this->assertEquals(["foo" => new DummyClass(), "bar" => 1], $this->parser->parse($input, false, true), "->parse() is able to parse objects");
    }

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

    public function testSequenceInAMapping()
    {
        $this->expectException("Scabbia\\Yaml\\ParseException");

        $this->parser->parse(<<<EOF
yaml:
  hash: me
  - array stuff
EOF
        );
    }

    public function testMappingInASequence()
    {
        $this->expectException("Scabbia\\Yaml\\ParseException");

        $this->parser->parse(<<<EOF
yaml:
  - array stuff
  hash: me
EOF
        );
    }

    public function testEmptyValue()
    {
        $input = <<<EOF
hash:
EOF;

        $this->assertEquals(["hash" => null], $this->parser->parse($input));
    }

    public function testStringBlockWithComments()
    {
        $this->assertEquals(["content" => <<<EOT
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOT
        ], $this->parser->parse(<<<EOF
content: |
    # comment 1
    header

        # comment 2
        <body>
            <h1>title</h1>
        </body>

    footer # comment3
EOF
        ));
    }

    public function testNestedStringBlockWithComments()
    {
        $this->assertEquals([["content" => <<<EOT
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOT
        ]], $this->parser->parse(<<<EOF
-
    content: |
        # comment 1
        header

            # comment 2
            <body>
                <h1>title</h1>
            </body>

        footer # comment3
EOF
        ));
    }
}
