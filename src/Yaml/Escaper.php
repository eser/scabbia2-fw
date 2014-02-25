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

namespace Scabbia\Yaml;

/**
 * Escaper encapsulates escaping rules for single and double-quoted
 * YAML strings
 *
 * @package     Scabbia\Yaml
 * @author      Matthew Lewinski <matthew@lewinski.org>
 * @since       2.0.0
 */
class Escaper
{
    /** @type string REGEX_CHARACTER_TO_ESCAPE Characters that would cause a dumped string to require double quoting */
    const REGEX_CHARACTER_TO_ESCAPE = "[\\x00-\\x1f]|\xc2\x85|\xc2\xa0|\xe2\x80\xa8|\xe2\x80\xa9";
    /** @type string REGEX_ESCAPED_CHARACTER Regex fragment that matches an escaped character in a double quoted string */
    const REGEX_ESCAPED_CHARACTER =
        "\\\\([0abt\tnvfre \\\"\\/\\\\N_LP]|x[0-9a-fA-F]{2}|u[0-9a-fA-F]{4}|U[0-9a-fA-F]{8})";


    /**
     * @type array $escapees Mapping arrays for escaping a double quoted string. The backslash is first to ensure
     * proper escaping because str_replace operates iteratively on the input arrays. This ordering of the characters
     * avoids the use of strtr, which performs more slowly
     */
    protected static $escapees = ["\\\\", "\\\"", "\"",
                                "\x00",  "\x01",  "\x02",  "\x03",  "\x04",  "\x05",  "\x06",  "\x07",
                                "\x08",  "\x09",  "\x0a",  "\x0b",  "\x0c",  "\x0d",  "\x0e",  "\x0f",
                                "\x10",  "\x11",  "\x12",  "\x13",  "\x14",  "\x15",  "\x16",  "\x17",
                                "\x18",  "\x19",  "\x1a",  "\x1b",  "\x1c",  "\x1d",  "\x1e",  "\x1f",
                                "\xc2\x85", "\xc2\xa0", "\xe2\x80\xa8", "\xe2\x80\xa9"];
    /**
     * @type array $escaped  Mapping arrays for escaping a double quoted string. The backslash is first to ensure
     * proper escaping because str_replace operates iteratively on the input arrays. This ordering of the characters
     * avoids the use of strtr, which performs more slowly
     */
    protected static $escaped  = ["\\\"", "\\\\", "\\\"",
                                "\\0",   "\\x01", "\\x02", "\\x03", "\\x04", "\\x05", "\\x06", "\\a",
                                "\\b",   "\\t",   "\\n",   "\\v",   "\\f",   "\\r",   "\\x0e", "\\x0f",
                                "\\x10", "\\x11", "\\x12", "\\x13", "\\x14", "\\x15", "\\x16", "\\x17",
                                "\\x18", "\\x19", "\\x1a", "\\e",   "\\x1c", "\\x1d", "\\x1e", "\\x1f",
                                "\\N", "\\_", "\\L", "\\P"];


    /**
     * Determines if a PHP value would require double quoting in YAML
     *
     * @param string $value A PHP value
     *
     * @return bool True if the value would require double quotes.
     */
    public static function requiresDoubleQuoting($value)
    {
        return preg_match("/" . self::REGEX_CHARACTER_TO_ESCAPE . "/u", $value);
    }

    /**
     * Escapes and surrounds a PHP value with double quotes
     *
     * @param string $value A PHP value
     *
     * @return string The quoted, escaped string
     */
    public static function escapeWithDoubleQuotes($value)
    {
        return sprintf("\"%s\"", str_replace(self::$escapees, self::$escaped, $value));
    }

    /**
     * Determines if a PHP value would require single quoting in YAML
     *
     * @param string $value A PHP value
     *
     * @return bool True if the value would require single quotes.
     */
    public static function requiresSingleQuoting($value)
    {
        return preg_match("/[ \\s ' \" \\: \\{ \\} \\[ \\] , & \\* \\# \\?] | \\A[ - ? | < > = ! % @ ` ]/x", $value);
    }

    /**
     * Escapes and surrounds a PHP value with single quotes
     *
     * @param string $value A PHP value
     *
     * @return string The quoted, escaped string
     */
    public static function escapeWithSingleQuotes($value)
    {
        return sprintf("'%s'", str_replace("'", "''", $value));
    }

    /**
     * Unescapes a single quoted string
     *
     * @param string $value A single quoted string
     *
     * @return string The unescaped string
     */
    public function unescapeSingleQuotedString($value)
    {
        return str_replace("''", "'", $value);
    }

    /**
     * Unescapes a double quoted string
     *
     * @param string $value A double quoted string
     *
     * @return string The unescaped string
     */
    public function unescapeDoubleQuotedString($value)
    {
        $self = $this;
        $callback = function ($match) use ($self) {
            return $self->unescapeCharacter($match[0]);
        };

        // evaluate the string
        return preg_replace_callback("/" . self::REGEX_ESCAPED_CHARACTER . "/u", $callback, $value);
    }

    /**
     * Unescapes a character that was found in a double-quoted string
     *
     * @param string $value An escaped character
     *
     * @return string The unescaped character
     */
    public function unescapeCharacter($value)
    {
        $tEncoding = mb_internal_encoding();
        $tChar = $value[1];

        if ($tChar === "0") {
            return "\x0";
        } elseif ($tChar === "a") {
            return "\x7";
        } elseif ($tChar === "b") {
            return "\x8";
        } elseif ($tChar === "t") {
            return "\t";
        } elseif ($tChar === "\t") {
            return "\t";
        } elseif ($tChar === "n") {
            return "\n";
        } elseif ($tChar === "v") {
            return "\xb";
        } elseif ($tChar === "f") {
            return "\xc";
        } elseif ($tChar === "r") {
            return "\xd";
        } elseif ($tChar === "e") {
            return "\x1b";
        } elseif ($tChar === " ") {
            return " ";
        } elseif ($tChar === "\"") {
            return "\"";
        } elseif ($tChar === "/") {
            return "/";
        } elseif ($tChar === "\\") {
            return "\\";
        } elseif ($tChar === "N") {
            // U+0085 NEXT LINE
            return mb_convert_encoding("\x00\x85", $tEncoding, "UCS-2BE");
        } elseif ($tChar === "_") {
            // U+00A0 NO-BREAK SPACE
            return mb_convert_encoding("\x00\xA0", $tEncoding, "UCS-2BE");
        } elseif ($tChar === "L") {
            // U+2028 LINE SEPARATOR
            return mb_convert_encoding("\x20\x28", $tEncoding, "UCS-2BE");
        } elseif ($tChar === "P") {
            // U+2029 PARAGRAPH SEPARATOR
            return mb_convert_encoding("\x20\x29", $tEncoding, "UCS-2BE");
        } elseif ($tChar === "x") {
            $char = pack("n", hexdec(substr($value, 2, 2)));

            return mb_convert_encoding($char, $tEncoding, "UCS-2BE");
        } elseif ($tChar === "u") {
            $char = pack("n", hexdec(substr($value, 2, 4)));

            return mb_convert_encoding($char, $tEncoding, "UCS-2BE");
        } elseif ($tChar === "U") {
            $char = pack("N", hexdec(substr($value, 2, 8)));

            return mb_convert_encoding($char, $tEncoding, "UCS-4BE");
        }
    }
}
