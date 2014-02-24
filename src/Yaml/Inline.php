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

use Scabbia\Yaml\Escaper;
use Scabbia\Yaml\ParseException;

/**
 * Inline implements a YAML parser for the YAML inline syntax
 *
 * @package     Scabbia\Yaml
 * @author      Fabien Potencier <fabien@symfony.com>
 * @since       2.0.0
 */
class Inline
{
    /**
     * @type string REGEX_QUOTED_STRING a regular expression pattern to match quoted strings
     */
    const REGEX_QUOTED_STRING = "(?:\"([^\"\\\\]*(?:\\\\.[^\"\\\\]*)*)\"|'([^']*(?:''[^']*)*)')";


    /**
     * Converts a YAML string to a PHP array
     *
     * @param string  $value                  A YAML string
     *
     * @throws ParseException If the YAML is not valid
     * @return array A PHP array representing the YAML string
     */
    public static function parse($value)
    {
        $value = trim($value);

        if (strlen($value) === 0) {
            return "";
        }

        $i = 0;
        if ($value[0] === "[") {
            $result = self::parseSequence($value, $i);
            ++$i;
        } elseif ($value[0] === "{") {
            $result = self::parseMapping($value, $i);
            ++$i;
        } else {
            $result = self::parseScalar($value, null, ["\"", "'"], $i);
        }

        // some comments are allowed at the end
        if (preg_replace("/\\s+#.*$/A", "", substr($value, $i))) {
            throw new ParseException(sprintf("Unexpected characters near \"%s\".", substr($value, $i)));
        }

        return $result;
    }

    /**
     * Parses a scalar to a YAML string
     *
     * @param scalar $scalar
     * @param string $delimiters
     * @param array  $stringDelimiters
     * @param int    &$i
     * @param bool   $evaluate
     *
     * @throws ParseException When malformed inline YAML string is parsed
     * @return string A YAML string
     */
    public static function parseScalar(
        $scalar,
        $delimiters = null,
        array $stringDelimiters = array("\"", "'"),
        &$i = 0,
        $evaluate = true
    ) {
        if (in_array($scalar[$i], $stringDelimiters)) {
            // quoted scalar
            $output = self::parseQuotedScalar($scalar, $i);

            if ($delimiters !== null) {
                $tmp = ltrim(substr($scalar, $i), " ");
                if (!in_array($tmp[0], $delimiters)) {
                    throw new ParseException(sprintf("Unexpected characters (%s).", substr($scalar, $i)));
                }
            }

            return $output;
        }

        // "normal" string
        if (!$delimiters) {
            $output = substr($scalar, $i);
            $i += strlen($output);

            // remove comments
            if (($strpos = strpos($output, " #")) !== false) {
                $output = rtrim(substr($output, 0, $strpos));
            }
        } elseif (preg_match("/^(.+?)(" . implode("|", $delimiters) . ")/", substr($scalar, $i), $match)) {
            $output = $match[1];
            $i += strlen($output);
        } else {
            throw new ParseException(sprintf("Malformed inline YAML string (%s).", $scalar));
        }

        if ($evaluate) {
            return self::evaluateScalar($output);
        }

        return $output;
    }

    /**
     * Parses a quoted scalar to YAML
     *
     * @param string $scalar
     * @param int    &$i
     *
     * @throws ParseException When malformed inline YAML string is parsed
     * @return string A YAML string
     */
    protected static function parseQuotedScalar($scalar, &$i)
    {
        if (!preg_match("/" . self::REGEX_QUOTED_STRING . "/Au", substr($scalar, $i), $match)) {
            throw new ParseException(sprintf("Malformed inline YAML string (%s).", substr($scalar, $i)));
        }

        $output = substr($match[0], 1, strlen($match[0]) - 2);

        $escaper = new Escaper();
        if ($scalar[$i] == "\"") {
            $output = $escaper->unescapeDoubleQuotedString($output);
        } else {
            $output = $escaper->unescapeSingleQuotedString($output);
        }

        $i += strlen($match[0]);

        return $output;
    }

    /**
     * Parses a sequence to a YAML string
     *
     * @param string $sequence
     * @param int    &$i
     *
     * @throws ParseException When malformed inline YAML string is parsed
     * @return string A YAML string
     */
    protected static function parseSequence($sequence, &$i = 0)
    {
        $output = [];
        $len = strlen($sequence);
        $i += 1;

        // [foo, bar, ...]
        while ($i < $len) {
            if ($sequence[$i] === "[") {
                // nested sequence
                $output[] = self::parseSequence($sequence, $i);
            } elseif ($sequence[$i] === "{") {
                // nested mapping
                $output[] = self::parseMapping($sequence, $i);
            } elseif ($sequence[$i] === "]") {
                return $output;
            } elseif ($sequence[$i] !== "," && $sequence[$i] !== " ") {
                $isQuoted = in_array($sequence[$i], ["\"", "'"]);
                $value = self::parseScalar($sequence, [",", "]"], ["\"", "'"], $i);

                if (!$isQuoted && strpos($value, ": ") !== false) {
                    // embedded mapping?
                    try {
                        $value = self::parseMapping("{" . $value . "}");
                    } catch (\InvalidArgumentException $e) {
                        // no, it's not
                    }
                }

                $output[] = $value;
                --$i;
            }

            ++$i;
        }

        throw new ParseException(sprintf("Malformed inline YAML string %s", $sequence));
    }

    /**
     * Parses a mapping to a YAML string
     *
     * @param string $mapping
     * @param int    &$i
     *
     * @throws ParseException When malformed inline YAML string is parsed
     * @return string A YAML string
     */
    protected static function parseMapping($mapping, &$i = 0)
    {
        $output = [];
        $len = strlen($mapping);
        $i += 1;

        // {foo: bar, bar:foo, ...}
        while ($i < $len) {
            if ($mapping[$i] === " " || $mapping[$i] === ",") {
                ++$i;
                continue;
            } elseif ($mapping[$i] === "}") {
                return $output;
            }

            // key
            $key = self::parseScalar($mapping, [":", " "], ["\"", "'"], $i, false);

            // value
            $done = false;
            while ($i < $len) {
                if ($mapping[$i] === "[") {
                    // nested sequence
                    $output[$key] = self::parseSequence($mapping, $i);
                    $done = true;
                } elseif ($mapping[$i] === "{") {
                    // nested mapping
                    $output[$key] = self::parseMapping($mapping, $i);
                    $done = true;
                } elseif ($mapping[$i] !== ":" && $mapping[$i] !== " ") {
                    $output[$key] = self::parseScalar($mapping, [",", "}"], ["\"", "'"], $i);
                    $done = true;
                    --$i;
                }

                ++$i;

                if ($done) {
                    continue 2;
                }
            }
        }

        throw new ParseException(sprintf("Malformed inline YAML string %s", $mapping));
    }

    /**
     * Evaluates scalars and replaces magic values
     *
     * @param string $scalar
     *
     * @return string A YAML string
     */
    protected static function evaluateScalar($scalar)
    {
        $scalar = trim($scalar);

        if (strtolower($scalar) === "null" || $scalar === "" || $scalar === "~") {
            return null;
        } elseif (strpos($scalar, "!str") === 0) {
            return (string) substr($scalar, 5);
        } elseif (strpos($scalar, "! ") === 0) {
            return intval(self::parseScalar(substr($scalar, 2)));
        } elseif (strpos($scalar, "!!php/object:") === 0) {
            return unserialize(substr($scalar, 13));
        } elseif (ctype_digit($scalar)) {
            $raw = $scalar;
            $cast = intval($scalar);

            if ($scalar[0] === "0") {
                return octdec($scalar);
            }

            if ((string)$raw === (string)$cast) {
                return $cast;
            }

            return $raw;
        } elseif ($scalar[0] === "-" && ctype_digit(substr($scalar, 1))) {
            $raw = $scalar;
            $cast = intval($scalar);

            if ($scalar[1] === "0") {
                return octdec($scalar);
            }

            if ((string)$raw === (string)$cast) {
                return $cast;
            }

            return $raw;
        } elseif (strtolower($scalar) === "true") {
            return true;
        } elseif (strtolower($scalar) === "false") {
            return false;
        } elseif (is_numeric($scalar)) {
            if ($scalar[0] . $scalar[1] === "0x") {
                return hexdec($scalar);
            }

            return floatval($scalar);
        } elseif (strcasecmp($scalar, ".inf") === 0 || strcasecmp($scalar, ".NaN") === 0) {
            return -log(0);
        } elseif (strcasecmp($scalar, "-.inf") === 0) {
            return log(0);
        } elseif (preg_match("/^(-|\\+)?[0-9,]+(\\.[0-9]+)?$/", $scalar)) {
            return floatval(str_replace(",", "", $scalar));
        } elseif (preg_match(self::getTimestampRegex(), $scalar)) {
            return strtotime($scalar);
        } else {
            return (string)$scalar;
        }
    }

    /**
     * Gets a regex that matches a YAML date
     *
     * @return string The regular expression
     *
     * @see http://www.yaml.org/spec/1.2/spec.html#id2761573
     */
    public static function getTimestampRegex()
    {
        return <<<EOF
        ~^
        (?P<year>[0-9][0-9][0-9][0-9])
        -(?P<month>[0-9][0-9]?)
        -(?P<day>[0-9][0-9]?)
        (?:(?:[Tt]|[ \t]+)
        (?P<hour>[0-9][0-9]?)
        :(?P<minute>[0-9][0-9])
        :(?P<second>[0-9][0-9])
        (?:\.(?P<fraction>[0-9]*))?
        (?:[ \t]*(?P<tz>Z|(?P<tz_sign>[-+])(?P<tz_hour>[0-9][0-9]?)
        (?::(?P<tz_minute>[0-9][0-9]))?))?)?
        $~x
EOF;
    }
}
