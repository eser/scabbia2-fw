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
 */

namespace Scabbia\Helpers;

/**
 * A bunch of utility methods for string manipulation
 *
 * @package     Scabbia\Helpers
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 *
 * @scabbia-compile
 *
 * @todo pluralize, singularize
 * @todo split Text functions into another file
 * @todo alternator, camel2underscore, underscore2camel
 */
class String
{
    /** @type string FILTER_VALIDATE_BOOLEAN a symbolic constant for boolean validation */
    const FILTER_VALIDATE_BOOLEAN = "scabbiaFilterValidateBoolean";
    /** @type string FILTER_SANITIZE_BOOLEAN a symbolic constant for boolean sanitization */
    const FILTER_SANITIZE_BOOLEAN = "scabbiaFilterSanitizeBoolean";
    /** @type string FILTER_SANITIZE_XSS   a symbolic constant for xss sanitization */
    const FILTER_SANITIZE_XSS = "scabbiaFilterSanitizeXss";


    /**
     * Constructor to prevent new instances of String class
     *
     * @return String
     */
    final private function __construct()
    {
    }

    /**
     * Clone method to prevent duplication of String class
     *
     * @return String
     */
    final private function __clone()
    {
    }

    /**
     * Unserialization method to prevent restoration of String class
     *
     * @return String
     */
    final private function __wakeup()
    {
    }

    /**
     * Default variables for Html utility set
     *
     * @type array $defaults array of default variables
     */
    public static $defaults = [
        "tab" => "\t",
        "eol" => PHP_EOL,

        "squote_replacement" => [["\\", "'"], ["\\\\", "\\'"]],
        "dquote_replacement" => [["\\", "\""], ["\\\\", "\\\""]],

        "baseconversion_url_chars" => "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:[]@!$'()*+,;",
        "baseconversion_base62_chars" => "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
    ];


    /**
     * Sets the default variables
     *
     * @param array $uDefaults variables to be set
     *
     * @return void
     */
    public static function setDefaults($uDefaults)
    {
        self::$defaults = $uDefaults + self::$defaults;
    }

    /**
     * Returns default encoding
     *
     * @return string name of the encoding
     */
    public static function getEncoding()
    {
        return ini_get("default_charset");
    }

    /**
     * Checks arguments in order and returns the value of the first expression that is not-null
     *
     * @param array $uValues values
     *
     * @return mixed first non-null expression in parameter list.
     */
    public static function coalesce(...$uValues)
    {
        foreach ($uValues as $tValue) {
            if ($tValue !== null) {
                if (is_array($tValue)) {
                    if (isset($tValue[0][$tValue[1]]) && $tValue[0][$tValue[1]] !== null) {
                        return $tValue[0][$tValue[1]];
                    }

                    continue;
                }

                return $tValue;
            }
        }

        return null;
    }

    /**
     * Prefixes all lines in the given string with the dashes or specified string
     *
     * @param string $uInput  original string
     * @param string $uPrefix the string will be added to beginning of all lines
     *
     * @return string prefixed string
     */
    public static function prefixLines($uInput, $uPrefix = "- ")
    {
        $tLines = explode(self::$defaults["eol"], $uInput);

        $tOutput = $tLines[0] . self::$defaults["eol"];
        $tCount = 0;
        foreach ($tLines as $tLine) {
            if ($tCount++ === 0) {
                continue;
            }

            $tOutput .= $uPrefix . $tLine . self::$defaults["eol"];
        }

        return $tOutput;
    }

    /**
     * Filters or sanitizes given value according to filter options
     *
     * @param mixed $uValue  original value
     * @param mixed $uFilter filter
     * @param array $uArgs   arguments
     *
     * @return mixed final output
     *
     * @todo recursive filtering option
     */
    public static function filter($uValue, $uFilter, ...$uArgs)
    {
        if ($uFilter === self::FILTER_VALIDATE_BOOLEAN) {
            if ($uValue === true || $uValue === "true" || $uValue === 1 || $uValue === "1" ||
                $uValue === false || $uValue === "false" || $uValue === 0 || $uValue === "0") {
                return true;
            }

            return false;
        }

        if ($uFilter === self::FILTER_SANITIZE_BOOLEAN) {
            if ($uValue === true || $uValue === "true" || $uValue === 1 || $uValue === "1") {
                return true;
            }

            return false;
        }

        if ($uFilter === self::FILTER_SANITIZE_XSS) {
            return self::xss($uValue);
        }

        if (is_callable($uFilter, true)) {
            return call_user_func($uFilter, $uValue, ...$uArgs);
        }

        return filter_var(...$uArgs);
    }

    /**
     * Replaces placeholders given in string and formats them
     *
     * @param string $uString original string with placeholders
     * @param array  $uArgs   arguments
     *
     * @return string final replaced output
     */
    public static function format($uString, ...$uArgs)
    {
        if (count($uArgs) > 0 && is_array($uArgs[0])) {
            $uArgs = $uArgs[0];
        }

        $tBrackets = [[null, ""]];
        $tQuoteChar = false;
        $tLastItem = 0;
        $tArrayItem = 1;

        for ($tPos = 0, $tLen = self::length($uString); $tPos < $tLen; $tPos++) {
            $tChar = self::substr($uString, $tPos, 1);

            if ($tChar === "\\") {
                $tBrackets[$tLastItem][$tArrayItem] .= self::substr($uString, ++$tPos, 1);
                continue;
            }

            if ($tQuoteChar === false && $tChar === "{") {
                ++$tLastItem;
                $tBrackets[$tLastItem] = [null, null];
                $tArrayItem = 1;
                continue;
            }

            if ($tLastItem > 0) {
                if ($tBrackets[$tLastItem][$tArrayItem] === null) {
                    if ($tChar === "'" || $tChar === "\"") {
                        $tQuoteChar = $tChar;
                        $tBrackets[$tLastItem][$tArrayItem] = "\""; // static text
                        $tChar = self::substr($uString, ++$tPos, 1);
                    } else {
                        if ($tChar === "!") {
                            $tBrackets[$tLastItem][$tArrayItem] = "!"; // dynamic text
                            $tChar = self::substr($uString, ++$tPos, 1);
                        } else {
                            if ($tChar === "@") {
                                $tBrackets[$tLastItem][$tArrayItem] = "@"; // parameter
                                $tChar = self::substr($uString, ++$tPos, 1);
                            } else {
                                $tBrackets[$tLastItem][$tArrayItem] = "@"; // parameter
                            }
                        }
                    }
                }

                if (self::substr($tBrackets[$tLastItem][$tArrayItem], 0, 1) === "\"") {
                    if ($tQuoteChar === $tChar) {
                        $tQuoteChar = false;
                        continue;
                    }

                    if ($tQuoteChar !== false) {
                        $tBrackets[$tLastItem][$tArrayItem] .= $tChar;
                        continue;
                    }

                    if ($tChar !== "," && $tChar !== "}") {
                        continue;
                    }
                }

                if ($tArrayItem === 1 && $tChar === "|" && $tBrackets[$tLastItem][0] === null) {
                    $tBrackets[$tLastItem][0] = $tBrackets[$tLastItem][1];
                    $tBrackets[$tLastItem][1] = null;
                    continue;
                }

                if ($tChar === ",") {
                    $tBrackets[$tLastItem][++$tArrayItem] = null;
                    continue;
                }

                if ($tChar === "}") {
                    $tFunc = array_shift($tBrackets[$tLastItem]);
                    foreach ($tBrackets[$tLastItem] as &$tItem) {
                        if ($tItem[0] === "\"") {
                            $tItem = self::substr($tItem, 1);
                        } elseif ($tItem[0] === "@") {
                            $tItem = $uArgs[self::substr($tItem, 1)];
                        } elseif ($tItem[0] === "!") {
                            $tItem = constant(self::substr($tItem, 1));
                        }
                    }

                    if ($tFunc !== null) {
                        $tString = call_user_func(self::substr($tFunc, 1), ...$tBrackets[$tLastItem]);
                    } else {
                        $tString = implode(", ", $tBrackets[$tLastItem]);
                    }

                    $tArrayItem = count($tBrackets[$tLastItem - 1]) - 1;
                    $tBrackets[$tLastItem - 1][$tArrayItem] .= $tString;
                    unset($tBrackets[$tLastItem]);
                    $tLastItem--;

                    continue;
                }
            }

            $tBrackets[$tLastItem][$tArrayItem] .= $tChar;
        }

        return $tBrackets[0][1];
    }

    /**
     * Displays structured information of given parameter in a fancy way
     *
     * @param mixed $uVariable variable
     * @param bool  $uOutput   whether return output as a function output or not
     *
     * @return string|null structure of given parameter
     */
    public static function vardump($uVariable, $uOutput = true)
    {
        $tVariable = $uVariable;
        $tType = gettype($tVariable);
        $tOut = "";
        static $sTabs = "";

        if ($tType === "boolean") {
            $tOut .= "<b>boolean</b>(" . (($tVariable) ? "true" : "false") . ")" . self::$defaults["eol"];
        } elseif ($tType === "double") {
            $tOut .= "<b>{$tType}</b>('" . number_format($tVariable, 22, ".", "") . "')" . self::$defaults["eol"];
        } elseif ($tType === "integer" || $tType === "string") {
            $tOut .= "<b>{$tType}</b>('{$tVariable}')" . self::$defaults["eol"];
        } elseif ($tType === "array" || $tType === "object") {
            if ($tType === "object") {
                $tType = get_class($tVariable);
                $tVariable = get_object_vars($tVariable);
            }

            $tCount = count($tVariable);
            $tOut .= "<b>{$tType}</b>({$tCount})";

            if ($tCount > 0) {
                $tOut .= " {" . self::$defaults["eol"];

                $sTabs .= self::$defaults["tab"];
                foreach ($tVariable as $tKey => $tVal) {
                    $tOut .= "{$sTabs}[{$tKey}] = ";
                    $tOut .= self::vardump($tVal, false);
                }
                $sTabs = substr($sTabs, 0, -1);

                $tOut .= "{$sTabs}}";
            }

            $tOut .= self::$defaults["eol"];
        } elseif ($tType === "resource") {
            $tOut .= "<b>resource</b>('" . get_resource_type($tVariable) . "')" . self::$defaults["eol"];
        } elseif ($tType === "NULL") {
            $tOut .= "<b><i>null</i></b>" . self::$defaults["eol"];
        } else {
            $tOut .= "<b>{$tType}</b>" . self::$defaults["eol"];
        }

        if ($uOutput) {
            echo "<pre>{$tOut}</pre>";

            return null;
        }

        return $tOut;
    }

    /**
     * A basic hash method
     *
     * @param mixed $uValue value is going to be hashed
     *
     * @return int hash in decimal representation
     */
    public static function hash($uValue)
    {
        return hexdec(hash("crc32", $uValue) . hash("crc32b", $uValue));
    }

    /**
     * Generates a random password in given length
     *
     * @param int $uLength password length
     *
     * @return string generated password
     */
    public static function generatePassword($uLength)
    {
        // mt_srand((int)(microtime(true) * 0xFFFF));

        static $sVowels = ["a", "e", "i", "o", "u"];
        static $sCons = [
            "b",
            "c",
            "d",
            "g",
            "h",
            "j",
            "k",
            "l",
            "m",
            "n",
            "p",
            "r",
            "s",
            "t",
            "u",
            "v",
            "w",
            "tr",
            "cr",
            "br",
            "fr",
            "th",
            "dr",
            "ch",
            "ph",
            "wr",
            "st",
            "sp",
            "sw",
            "pr",
            "sl",
            "cl"
        ];

        $tConsLen = count($sCons) - 1;
        $tVowelsLen = count($sVowels) - 1;
        for ($tOutput = ""; strlen($tOutput) < $uLength;) {
            $tOutput .= $sCons[mt_rand(0, $tConsLen)] . $sVowels[mt_rand(0, $tVowelsLen)];
        }

        // prevent overflow of size
        return substr($tOutput, 0, $uLength);
    }

    /**
     * Generates an unique-identifier and ensures it is in uuid format
     *
     * @return string generated uuid
     */
    public static function generateUuid()
    {
        if (function_exists("com_create_guid")) {
            return strtolower(trim(com_create_guid(), "{}"));
        }

        // mt_srand((int)(microtime(true) * 0xFFFF));

        // return md5(uniqid(mt_rand(), true));
        return sprintf(
            "%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Generates a random string in given length
     *
     * @param int    $uLength  string length
     * @param string $uCharset set of chars are going to be used in generated string
     *
     * @return string generated string
     */
    public static function generate($uLength, $uCharset = "0123456789ABCDEF")
    {
        // mt_srand((int)(microtime(true) * 0xFFFF));

        $tCharsetLen = self::length($uCharset) - 1;
        for ($tOutput = ""; $uLength > 0; $uLength--) {
            $tOutput .= self::substr($uCharset, mt_rand(0, $tCharsetLen), 1);
        }

        return $tOutput;
    }

    /**
     * Filters the chars that occur XSS attacks
     *
     * @param string $uValue original value
     *
     * @return string filtered string
     */
    public static function xss($uValue)
    {
        if (!is_string($uValue)) {
            return $uValue;
        }

        return str_replace(
            [
                "<",
                ">",
                "\"",
                "'",
                "$",
                "(",
                ")",
                "%28",
                "%29"
            ],
            [
                "&#60;",
                "&#62;",
                "&#34;",
                "&#39;",
                "&#36;",
                "&#40;",
                "&#41;",
                "&#40;",
                "&#41;"
            ],
            $uValue
        ); // "&" => "&#38;"
    }

    /**
     * Strips given string and leaves only chars specified
     *
     * @param string $uString     original string
     * @param string $uValidChars set of chars are going to be allowed
     *
     * @return string stripped output
     */
    public static function strip($uString, $uValidChars)
    {
        $tOutput = "";

        for ($tCount = 0, $tLen = self::length($uString); $tCount < $tLen; $tCount++) {
            $tChar = self::substr($uString, $tCount, 1);
            if (self::strpos($uValidChars, $tChar) === false) {
                continue;
            }

            $tOutput .= $tChar;
        }

        return $tOutput;
    }

    /**
     * Escapes single quotes in a string
     *
     * @param string $uString original string
     * @param bool   $uCover  whether cover output with single quotes or not
     *
     * @return string final output
     */
    public static function squote($uString, $uCover = false)
    {
        // if ($uString === null) {
        //     return "null";
        // }

        if ($uCover) {
            return "'" .
                str_replace(
                    self::$defaults["squote_replacement"][0],
                    self::$defaults["squote_replacement"][1],
                    $uString
                ) .
                "'";
        }

        return str_replace(
            self::$defaults["squote_replacement"][0],
            self::$defaults["squote_replacement"][1],
            $uString
        );
    }

    /**
     * Escapes double quotes in a string
     *
     * @param string $uString original string
     * @param bool   $uCover  whether cover output with double quotes or not
     *
     * @return string final output
     */
    public static function dquote($uString, $uCover = false)
    {
        // if ($uString === null) {
        //     return "null";
        // }

        if ($uCover) {
            return "'" .
            str_replace(
                self::$defaults["dquote_replacement"][0],
                self::$defaults["dquote_replacement"][1],
                $uString
            ) .
            "'";
        }

        return str_replace(
            self::$defaults["dquote_replacement"][0],
            self::$defaults["dquote_replacement"][1],
            $uString
        );
    }

    /**
     * Escapes single quotes in a set of strings
     *
     * @param string $uArray set of original strings
     * @param bool   $uCover whether cover output with single quotes or not
     *
     * @return array final output
     */
    public static function squoteArray($uArray, $uCover = false)
    {
        $tArray = [];
        foreach ((array)$uArray as $tKey => $tValue) {
            if ($uCover) {
                $tArray[$tKey] = "'" .
                    str_replace(
                        self::$defaults["squote_replacement"][0],
                        self::$defaults["squote_replacement"][1],
                        $tValue
                    ) .
                    "'";
                continue;
            }

            $tArray[$tKey] = str_replace(
                self::$defaults["squote_replacement"][0],
                self::$defaults["squote_replacement"][1],
                $tValue
            );
        }

        return $tArray;
    }

    /**
     * Escapes double quotes in a set of strings
     *
     * @param string $uArray set of original strings
     * @param bool   $uCover whether cover output with double quotes or not
     *
     * @return array final output
     */
    public static function dquoteArray($uArray, $uCover = false)
    {
        $tArray = [];
        foreach ((array)$uArray as $tKey => $tValue) {
            if ($uCover) {
                $tArray[$tKey] = "\"" .
                    str_replace(
                        self::$defaults["dquote_replacement"][0],
                        self::$defaults["dquote_replacement"][1],
                        $tValue
                    ) .
                    "\"";
                continue;
            }

            $tArray[$tKey] = str_replace(
                self::$defaults["dquote_replacement"][0],
                self::$defaults["dquote_replacement"][1],
                $tValue
            );
        }

        return $tArray;
    }

    /**
     * Replaces CRLF characters with given string
     *
     * @param string $uString original string
     * @param string $uBreaks string that breaks are replaced with.
     *
     * @return string final output
     */
    public static function replaceBreaks($uString, $uBreaks = "<br />")
    {
        return str_replace(["\r", "\n"], ["", $uBreaks], $uString);
    }

    /**
     * Cuts a string if it exceeds given length
     *
     * @param string $uString original string
     * @param int    $uLength maximum length
     * @param string $uSuffix a suffix that is going to be added to end of string if original string is cut
     *
     * @return string final output
     */
    public static function cut($uString, $uLength, $uSuffix = "...")
    {
        if (self::length($uString) <= $uLength) {
            return $uString;
        }

        return rtrim(self::substr($uString, 0, $uLength)) . $uSuffix;
    }

    /**
     * Encodes html characters
     *
     * @param string $uString original string that is going to be encoded
     *
     * @return string encoded string
     */
    public static function encodeHtml($uString)
    {
        return str_replace(
            ["&", "\"", "<", ">"],
            ["&amp;", "&quot;", "&lt;", "&gt;"],
            $uString
        );
    }

    /**
     * Decodes encoded html characters
     *
     * @param string $uString original string that has encoded characters
     *
     * @return string decoded string
     */
    public static function decodeHtml($uString)
    {
        return str_replace(
            ["&amp;", "&quot;", "&lt;", "&gt;"],
            ["&", "\"", "<", ">"],
            $uString
        );
    }

    /**
     * Escapes special html characters
     *
     * @param string $uString original string that is going to be escaped
     *
     * @return string escaped string
     */
    public static function escapeHtml($uString)
    {
        return htmlspecialchars($uString, ENT_COMPAT | ENT_HTML5);
    }

    /**
     * Unescapes escaped html characters
     *
     * @param string $uString original string that has escaped characters
     *
     * @return string unescaped string
     */
    public static function unescapeHtml($uString)
    {
        return htmlspecialchars_decode($uString, ENT_COMPAT | ENT_HTML5);
    }

    /**
     * Transforms the string to lowercase
     *
     * @param string $uString original string
     *
     * @return string lowercased string
     */
    public static function toLower($uString)
    {
        return mb_convert_case($uString, MB_CASE_LOWER);
    }

    /**
     * Transforms the string to uppercase
     *
     * @param string $uString original string
     *
     * @return string uppercased string
     */
    public static function toUpper($uString)
    {
        return mb_convert_case($uString, MB_CASE_UPPER);
    }

    /**
     * Capitalizes the first character of the given string
     *
     * @param string $uString original string
     *
     * @return string the string with the first character capitalized
     */
    public static function capitalize($uString)
    {
        return mb_convert_case($uString, MB_CASE_TITLE);
    }

    /**
     * Capitalizes all first characters of the words in the given string
     *
     * @param string $uString original string
     * @param string $uSpace  the space character
     *
     * @return string the string with the first characters of the words capitalized
     */
    public static function capitalizeWords($uString, $uSpace = " ")
    {
        $tOutput = "";
        $tCapital = true;

        for ($tPos = 0, $tLen = self::length($uString); $tPos < $tLen; $tPos++) {
            $tChar = self::substr($uString, $tPos, 1);

            if ($tChar === $uSpace) {
                $tCapital = true;
            } elseif ($tCapital) {
                $tOutput .= self::toUpper($tChar);
                $tCapital = false;
                continue;
            }

            $tOutput .= $tChar;
        }

        return $tOutput;
    }

    /**
     * Returns the length of the string
     *
     * @param string $uString the input string
     *
     * @return int string length
     */
    public static function length($uString)
    {
        // return mb_strlen($uString);
        return strlen(utf8_decode($uString));
    }

    /**
     * Checks the string if it starts with another string.
     *
     * @param string $uString the input string
     * @param string $uNeedle another string
     *
     * @return bool true if first string starts with second one
     */
    public static function startsWith($uString, $uNeedle)
    {
        // $tLength = mb_strlen($uNeedle);
        $tLength = strlen(utf8_decode($uNeedle));
        if ($tLength === 0) {
            return true;
        }

        return (mb_substr($uString, 0, $tLength) === $uNeedle);
    }

    /**
     * Checks the string if it ends with another string
     *
     * @param string $uString the input string
     * @param string $uNeedle another string
     *
     * @return bool true if first string ends with second one
     */
    public static function endsWith($uString, $uNeedle)
    {
        // $tLength = mb_strlen($uNeedle);
        $tLength = strlen(utf8_decode($uNeedle));
        if ($tLength === 0) {
            return true;
        }

        return (mb_substr($uString, -$tLength) === $uNeedle);
    }

    /**
     * Returns part of the string
     *
     * @param string $uString original string
     * @param int    $uStart  start offset
     * @param int    $uLength length
     *
     * @return string sliced substring
     */
    public static function substr($uString, $uStart, $uLength = null)
    {
        if ($uLength === null) {
            return mb_substr($uString, $uStart);
        }

        return mb_substr($uString, $uStart, $uLength);
    }

    /**
     * Find the offset of the first occurrence of given string
     *
     * @param string $uString the input string
     * @param string $uNeedle another string
     * @param int    $uOffset start offset
     *
     * @return int position of first occurence
     */
    public static function strpos($uString, $uNeedle, $uOffset = 0)
    {
        return mb_strpos($uString, $uNeedle, $uOffset);
    }

    /**
     * Returns the substring starting from and including the first occurrence of given string
     *
     * @param string $uString       the input string
     * @param string $uNeedle       another string
     * @param bool   $uBeforeNeedle start offset
     *
     * @return string sliced substring, or false if string is not found
     */
    public static function strstr($uString, $uNeedle, $uBeforeNeedle = false)
    {
        return mb_strstr($uString, $uNeedle, $uBeforeNeedle);
    }

    /**
     * Returns the given bytes in short representation
     *
     * @param int $uSize      size
     * @param int $uPrecision precision
     *
     * @return string short representation
     */
    public static function sizeCalc($uSize, $uPrecision = 0)
    {
        static $sSize = " KMGT";
        for ($tCount = 0; $uSize >= 1024; $uSize /= 1024, $tCount++) {
            ;
        }

        return round($uSize, $uPrecision) . " {$sSize[$tCount]}B";
    }

    /**
     * Returns the given number in short representation
     *
     * @param int $uSize      size
     * @param int $uPrecision precision
     *
     * @return string short representation
     */
    public static function quantityCalc($uSize, $uPrecision = 0)
    {
        static $sSize = " KMGT";
        for ($tCount = 0; $uSize >= 1000; $uSize /= 1000, $tCount++) {
            ;
        }

        return round($uSize, $uPrecision) . $sSize[$tCount];
    }

    /**
     * Returns the given period in short representation
     *
     * @param int $uTime time period
     *
     * @return string short representation
     */
    public static function timeCalc($uTime)
    {
        if ($uTime >= 60) {
            return number_format($uTime / 60, 2, ".", "") . "m";
        }

        if ($uTime >= 1) {
            return number_format($uTime, 2, ".", "") . "s";
        }

        return number_format($uTime * 1000, 2, ".", "") . "ms";
    }

    /**
     * Removes accented characters in a string
     *
     * @param string $uString the input string
     *
     * @return string unaccented string
     */
    public static function removeAccent($uString)
    {
        static $tAccented = [
            "À",
            "Á",
            "Â",
            "Ã",
            "Ä",
            "Å",
            "Æ",
            "Ç",
            "È",
            "É",
            "Ê",
            "Ë",
            "Ì",
            "Í",
            "Î",
            "Ï",
            "Ð",
            "Ñ",
            "Ò",
            "Ó",
            "Ô",
            "Õ",
            "Ö",
            "Ø",
            "Ù",
            "Ú",
            "Û",
            "Ü",
            "Ý",
            "ß",
            "à",
            "á",
            "â",
            "ã",
            "ä",
            "å",
            "æ",
            "ç",
            "è",
            "é",
            "ê",
            "ë",
            "ì",
            "í",
            "î",
            "ï",
            "ñ",
            "ò",
            "ó",
            "ô",
            "õ",
            "ö",
            "ø",
            "ù",
            "ú",
            "û",
            "ü",
            "ý",
            "ÿ",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "Œ",
            "œ",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "Š",
            "š",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "Ÿ",
            "?",
            "?",
            "?",
            "?",
            "Ž",
            "ž",
            "?",
            "ƒ",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "þ",
            "Þ",
            "ð"
        ];
        static $tStraight = [
            "A",
            "A",
            "A",
            "A",
            "A",
            "A",
            "AE",
            "C",
            "E",
            "E",
            "E",
            "E",
            "I",
            "I",
            "I",
            "I",
            "D",
            "N",
            "O",
            "O",
            "O",
            "O",
            "O",
            "O",
            "U",
            "U",
            "U",
            "U",
            "Y",
            "s",
            "a",
            "a",
            "a",
            "a",
            "a",
            "a",
            "ae",
            "c",
            "e",
            "e",
            "e",
            "e",
            "i",
            "i",
            "i",
            "i",
            "n",
            "o",
            "o",
            "o",
            "o",
            "o",
            "o",
            "u",
            "u",
            "u",
            "u",
            "y",
            "y",
            "A",
            "a",
            "A",
            "a",
            "A",
            "a",
            "C",
            "c",
            "C",
            "c",
            "C",
            "c",
            "C",
            "c",
            "D",
            "d",
            "D",
            "d",
            "E",
            "e",
            "E",
            "e",
            "E",
            "e",
            "E",
            "e",
            "E",
            "e",
            "G",
            "g",
            "G",
            "g",
            "G",
            "g",
            "G",
            "g",
            "H",
            "h",
            "H",
            "h",
            "I",
            "i",
            "I",
            "i",
            "I",
            "i",
            "I",
            "i",
            "I",
            "i",
            "IJ",
            "ij",
            "J",
            "j",
            "K",
            "k",
            "L",
            "l",
            "L",
            "l",
            "L",
            "l",
            "L",
            "l",
            "l",
            "l",
            "N",
            "n",
            "N",
            "n",
            "N",
            "n",
            "n",
            "O",
            "o",
            "O",
            "o",
            "O",
            "o",
            "OE",
            "oe",
            "R",
            "r",
            "R",
            "r",
            "R",
            "r",
            "S",
            "s",
            "S",
            "s",
            "S",
            "s",
            "S",
            "s",
            "T",
            "t",
            "T",
            "t",
            "T",
            "t",
            "U",
            "u",
            "U",
            "u",
            "U",
            "u",
            "U",
            "u",
            "U",
            "u",
            "U",
            "u",
            "W",
            "w",
            "Y",
            "y",
            "Y",
            "Z",
            "z",
            "Z",
            "z",
            "Z",
            "z",
            "s",
            "f",
            "O",
            "o",
            "U",
            "u",
            "A",
            "a",
            "I",
            "i",
            "O",
            "o",
            "U",
            "u",
            "U",
            "u",
            "U",
            "u",
            "U",
            "u",
            "U",
            "u",
            "A",
            "a",
            "AE",
            "ae",
            "O",
            "o",
            "b",
            "B",
            "o"
        ];

        return str_replace($tAccented, $tStraight, $uString);
    }

    /**
     * Removes invisible characters in a string
     *
     * @param string $uString the input string
     *
     * @return string string with invisible characters removed
     */
    public static function removeInvisibles($uString)
    {
        static $tInvisibles = [
            0,
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
            11,
            12,
            14,
            15,
            16,
            17,
            18,
            19,
            20,
            21,
            22,
            23,
            24,
            25,
            26,
            27,
            28,
            29,
            30,
            31,
            127
        ];
        $tOutput = "";

        for ($tCount = 0, $tLen = self::length($uString); $tCount < $tLen; $tCount++) {
            $tChar = self::substr($uString, $tCount, 1);

            if (in_array(ord($tChar), $tInvisibles)) {
                continue;
            }

            $tOutput .= $tChar;
        }

        return $tOutput;
    }

    /**
     * Returns a transformed string that can be used as unixname and url portion
     *
     * @param string $uString    the input string
     * @param string $uSpaceChar char that is used for spacing
     *
     * @return string transformed string
     */
    public static function slug($uString, $uSpaceChar = "-")
    {
        $uString = self::removeInvisibles($uString);
        $uString = self::removeAccent($uString);
        $uString = strtolower(trim($uString));
        $uString = preg_replace("/[^a-z0-9-]/", $uSpaceChar, $uString);
        $uString = preg_replace("/-+/", $uSpaceChar, $uString);

        return $uString;
    }

    /**
     * Converts a number to specified base
     *
     * @param int    $uNumber    the input number
     * @param string $uBaseChars charset available for target base
     *
     * @return string converted base
     */
    public static function toBase($uNumber, $uBaseChars)
    {
        $tBaseLength = strlen($uBaseChars);
        $tResult = "";

        do {
            $tIndex = $uNumber % $tBaseLength;
            // if ($tIndex < 0) {
            //    $tIndex += $tBaseLength;
            // }

            $tResult = $uBaseChars[$tIndex] . $tResult;
            $uNumber = ($uNumber - $tIndex) / $tBaseLength;
        } while ($uNumber > 0);

        return $tResult;
    }

    /**
     * Converts a number from specified base
     *
     * @param int    $uNumber    the input number
     * @param string $uBaseChars charset available for source base
     *
     * @return string converted base
     */
    public static function fromBase($uNumber, $uBaseChars)
    {
        $tBaseLength = strlen($uBaseChars);
        $tResult = strpos($uBaseChars, $uNumber[0]);

        for ($i = 1, $tLength = strlen($uNumber); $i < $tLength; $i++) {
            $tResult = ($tBaseLength * $tResult) + strpos($uBaseChars, $uNumber[$i]);
        }

        return $tResult;
    }

    /**
     * Converts a number to base62
     *
     * @param int $uNumber the input number
     *
     * @return string converted base
     */
    public static function toBase62($uNumber)
    {
        return self::toBase($uNumber, self::$defaults["baseconversion_base62_chars"]);
    }

    /**
     * Converts a number from base62
     *
     * @param int $uNumber the input number
     *
     * @return string converted base
     */
    public static function fromBase62($uNumber)
    {
        return self::fromBase($uNumber, self::$defaults["baseconversion_base62_chars"]);
    }

    /**
     * Converts a number to url base
     *
     * @param int $uNumber the input number
     *
     * @return string converted base
     */
    public static function toBaseUrl($uNumber)
    {
        return self::toBase($uNumber, self::$defaults["baseconversion_url_chars"]);
    }

    /**
     * Converts a number from url base
     *
     * @param int $uNumber the input number
     *
     * @return string converted base
     */
    public static function fromBaseUrl($uNumber)
    {
        return self::fromBase($uNumber, self::$defaults["baseconversion_url_chars"]);
    }

    /**
     * Shortens a uuid to use it in compact format
     *
     * @param string $uString the input string
     *
     * @return string shortened uuid
     */
    public static function shortenUuid($uString)
    {
        $tParts = [
            substr($uString, 0, 8),
            substr($uString, 9, 4),
            substr($uString, 14, 4),
            substr($uString, 19, 4),
            substr($uString, 24, 6),
            substr($uString, 30, 6)
        ];

        $tShortened = "";
        foreach ($tParts as $tPart) {
            $tEncoded = base_convert($tPart, 16, 10);
            $tShortened .= self::toBase($tEncoded, self::$defaults["baseconversion_url_chars"]);
        }

        return $tShortened;
    }

    /**
     * Unshortens a uuid to restore it in compact format
     *
     * @param string $uString the input string
     *
     * @return string unshortened uuid
     */
    public static function unshortenUuid($uString)
    {
        $tParts = [
            substr($uString, 0, 5),
            substr($uString, 5, 3),
            substr($uString, 8, 3),
            substr($uString, 11, 3),
            substr($uString, 14, 4),
            substr($uString, 18, 4)
        ];

        $tUnshortened = "";
        $tIndex = 0;
        foreach ($tParts as $tPart) {
            $tDecoded = self::fromBase($tPart, self::$defaults["baseconversion_url_chars"]);
            $tUnshortened .= base_convert($tDecoded, 10, 16);
            if ($tIndex++ <= 3) {
                $tUnshortened .= "-";
            }
        }

        return $tUnshortened;
    }

    /**
     * Returns the ordinal pronounce of a number
     *
     * @param int $uNumber the input number
     *
     * @return string ordinal string
     */
    public static function ordinalize($uNumber)
    {
        if (in_array(($uNumber % 100), range(11, 13))) {
            return $uNumber . "th";
        }

        $tMod = $uNumber % 10;
        if ($tMod === 1) {
            return $uNumber . "st";
        } elseif ($tMod === 2) {
            return $uNumber . "nd";
        } elseif ($tMod === 3) {
            return $uNumber . "rd";
        } else {
            return $uNumber . "th";
        }
    }

    /**
     * Swaps two variables in memory
     *
     * @param mixed $uVariable1 first variable
     * @param mixed $uVariable2 second variable
     *
     * @return void
     */
    public static function swap(&$uVariable1, &$uVariable2)
    {
        $tTemp = $uVariable1;
        $uVariable1 = $uVariable2;
        $uVariable2 = $tTemp;
    }

    /**
     * Sanitize a filename to prevent filesystem vulnerabilities
     *
     * @param string $uFilename     the input filename
     * @param bool   $uRemoveAccent whether accented characters are going to be removed or not
     * @param bool   $uRemoveSpaces whether spacing is going to be replaced with dashes or not
     *
     * @return string sanitized filename
     *
     * @todo optionally explode by '/', sanitize between
     */
    public static function sanitizeFilename($uFilename, $uRemoveAccent = false, $uRemoveSpaces = false)
    {
        static $sReplaceChars = [
            [
                "\\",
                "/",
                ":",
                "?",
                "*",
                "'",
                "\"",
                "<",
                ">",
                "|",
                ".",
                "+"
            ],
            [
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-",
                "-"
            ]
        ];

        $tPathInfo = pathinfo($uFilename);
        $tFilename = str_replace($sReplaceChars[0], $sReplaceChars[1], $tPathInfo["filename"]);

        if (isset($tPathInfo["extension"])) {
            $tFilename .= "." . str_replace($sReplaceChars[0], $sReplaceChars[1], $tPathInfo["extension"]);
        }

        $tFilename = self::removeInvisibles($tFilename);
        if ($uRemoveAccent) {
            $tFilename = self::removeAccent($tFilename);
        }

        if ($uRemoveSpaces) {
            $tFilename = str_replace(" ", "_", $tFilename);
        }

        if (isset($tPathInfo["dirname"]) && $tPathInfo["dirname"] !== ".") {
            return rtrim(str_replace("\\", "/", $tPathInfo["dirname"]), "/") . "/{$tFilename}";
        }

        return $tFilename;
    }

    /**
     * Returns the best matching path among the alternatives
     *
     * @param array  $uPathList set of paths
     * @param string $uFullPath the full path
     *
     * @return string|false the path if found, false otherwise
     */
    public function matchPaths($uPathList, $uFullPath)
    {
        $uFullPath = ltrim(str_replace("\\", "/", $uFullPath), "/");

        $tLastFound = [0, false];

        foreach ($uPathList as $tKey) {
            $tKey = trim(str_replace("\\", "/", $tKey), "/") . "/";
            $tKeyLength = strlen($tKey);

            if ($tLastFound[0] < $tKeyLength && strpos($uFullPath, $tKey) === 0) {
                $tLastFound = [$tKeyLength, $tKey];
            }
        }

        return $tLastFound[1];
    }

    /**
     * Captures and replaces http links in a string
     *
     * @param string   $uString   the input string
     * @param callable $uCallback closure that gets each url address as a parameter and outputs replaced string
     *
     * @return string updated string
     */
    public static function convertLinks($uString, /* callable */ $uCallback)
    {
        return preg_replace_callback(
            "#((https?://)?([-\\w]+\\.[-\\w\\.]+)+\\w(:\\d+)?(/([-\\w/_\\.]*(\\?\\S+)?)?)*)#",
            $uCallback,
            $uString
        );
    }
}
