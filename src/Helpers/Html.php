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

use Scabbia\Helpers\Arrays;
use Scabbia\Helpers\String;

/**
 * A bunch of utility methods for creating html elements
 *
 * @package     Scabbia\Helpers
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 *
 * @scabbia-compile
 *
 * @todo form open
 * @todo form fields
 * @todo form csrf protection
 * @todo Html::list (<li> every row, nested)
 * @todo Html::br (<br /> implode)
 * @todo Html::image
 * @todo Html::anchor
 * @todo Html::anchorEmail
 * @todo Html::textarea
 * @todo Html::button
 */
class Html
{
    /**
     * Default variables for Html utility set
     *
     * @type array $defaults array of default variables
     */
    public static $defaults = [
        "pager_pagesize" => 10,
        "pager_numlinks" => 10,

        "pager_firstlast" => true,
        "pager_first" => "&lt;&lt;",
        "pager_prev" => "&lt;",
        "pager_next" => "&gt;",
        "pager_last" => "&gt;&gt;",

        "pager_divider" => "",
        "pager_dots" => " ... ",

        "table_table" => "<table>",
        "table_header" => "<th>{value}</th>",
        "table_cell" => "<td>{value}</td>"
    ];

    /**
     * Set of attribute placement order
     *
     * @type array $attributeOrder array of attribute orders
     */
    public static $attributeOrder = [
        "action", "method", "type", "id", "name", "value",
        "href", "src", "width", "height", "cols", "rows",
        "size", "maxlength", "rel", "media", "accept-charset",
        "accept", "tabindex", "accesskey", "alt", "title", "class",
        "style", "selected", "checked", "readonly", "disabled"
    ];


    /**
     * Constructor to prevent new instances of Html class
     *
     * @return Html
     */
    final private function __construct()
    {
    }

    /**
     * Clone method to prevent duplication of Html class
     *
     * @return Html
     */
    final private function __clone()
    {
    }

    /**
     * Unserialization method to prevent restoration of Html class
     *
     * @return Html
     */
    final private function __wakeup()
    {
    }

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
     * Creates an Html tag
     *
     * @param string      $uName       name of the element
     * @param array       $uAttributes set of the tag attributes
     * @param null|string $uValue      value
     *
     * @return string html output
     */
    public static function tag($uName, array $uAttributes = [], $uValue = null)
    {
        $tReturn = "<{$uName}";
        if (count($uAttributes) > 0) {
            $tReturn .= " " . self::attributes($uAttributes);
        }

        if ($uValue === null) {
            $tReturn .= " />";
        } else {
            $tReturn .= ">{$uValue}</{$uName}>";
        }

        return $tReturn;
    }

    /**
     * Creates attributes array
     *
     * @param array $uAttributes set of the tag attributes
     *
     * @return string html output
     */
    public static function attributes(array $uAttributes)
    {
        $tAttributes = Arrays::sortByPriority($uAttributes, self::$attributeOrder);

        $tReturn = [];
        foreach ($tAttributes as $tKey => $tValue) {
            if ($tValue === null) {
                $tReturn[] = "{$tKey}=\"{$tKey}\"";
                continue;
            }

            $tReturn[] = "{$tKey}=\"" . String::escapeHtml($tValue) . "\"";
        }

        return implode(" ", $tReturn);
    }

    /**
     * Creates options array for select element
     *
     * @param array  $uOptions set of values
     * @param mixed  $uDefault default selected value
     * @param mixed  $uField   field key for array values
     * @param string $uExtra   additional markup for each option tag
     *
     * @return string html output
     */
    public static function selectOptions(array $uOptions, $uDefault = null, $uField = null, $uExtra = "")
    {
        $tOutput = "";

        foreach ($uOptions as $tKey => $tVal) {
            $tOutput .= "<option value=\"" . String::dquote($tKey) . "\"";
            if ($uDefault === $tKey) {
                $tOutput .= " selected=\"selected\"";
            }

            $tOutput .= "{$uExtra}>" . ($uField !== null ? $tVal[$uField] : $tVal) . "</option>";
        }

        return $tOutput;
    }

    /**
     * Creates options array for select element and returns it in array
     *
     * @param array  $uOptions set of values
     * @param mixed  $uDefault default selected value
     * @param mixed  $uField   field key for array values
     * @param string $uExtra   additional markup for each option tag
     *
     * @return string set of html outputs
     */
    public static function selectOptionsArray(array $uOptions, $uDefault = null, $uField = null, $uExtra = "")
    {
        $tOutput = [];

        foreach ($uOptions as $tKey => $tVal) {
            $tItem = "<option value=\"" . String::dquote($tKey) . "\"";
            if ($uDefault === $tKey) {
                $tItem .= " selected=\"selected\"";
            }

            $tItem .= "{$uExtra}>" . ($uField !== null ? $tVal[$uField] : $tVal) . "</option>";
            $tOutput[] = $tItem;
        }

        return $tOutput;
    }

    /**
     * Creates options array for input type="radio" element
     *
     * @param string $uName    name of the element
     * @param array  $uOptions set of values
     * @param mixed  $uDefault default selected value
     * @param mixed  $uField   field key for array values
     * @param string $uExtra   additional markup for each option tag
     *
     * @return string html output
     */
    public static function radioOptions($uName, array $uOptions, $uDefault = null, $uField = null, $uExtra = "")
    {
        $tOutput = "";

        foreach ($uOptions as $tKey => $tVal) {
            $tOutput .= "<label";

            if ($uDefault === $tKey) {
                $tOutput .= " class=\"selected\"";
            }

            $tOutput .= "><input type=\"radio\" name=\"" .
                String::dquote($uName) .
                "\" value=\"" . String::dquote($tKey) . "\"";

            if ($uDefault === $tKey) {
                $tOutput .= " checked=\"checked\"";
            }

            if (strlen($uExtra) > 0) {
                $tOutput .= " {$uExtra}";
            }

            $tOutput .= " />" . ($uField !== null ? $tVal[$uField] : $tVal) . "</label>";
        }

        return $tOutput;
    }

    /**
     * Creates options array for input type="radio" element and returns it in array
     *
     * @param string $uName    name of the element
     * @param array  $uOptions set of values
     * @param mixed  $uDefault default selected value
     * @param mixed  $uField   field key for array values
     * @param string $uExtra   additional markup for each option tag
     *
     * @return string set of html outputs
     */
    public static function radioOptionsArray($uName, array $uOptions, $uDefault = null, $uField = null, $uExtra = "")
    {
        $tOutput = [];

        foreach ($uOptions as $tKey => $tVal) {
            $tItem = "<label";

            if ($uDefault === $tKey) {
                $tItem .= " class=\"selected\"";
            }

            $tItem .= "><input type=\"radio\" name=\"" .
                String::dquote($uName) . "\" value=\"" .
                String::dquote($tKey) . "\"";

            if ($uDefault === $tKey) {
                $tItem .= " checked=\"checked\"";
            }

            if (strlen($uExtra) > 0) {
                $tOutput .= " {$uExtra}";
            }

            $tItem .= " />" . ($uField !== null ? $tVal[$uField] : $tVal) . "</label>";
            $tOutput[] = $tItem;
        }

        return $tOutput;
    }

    /**
     * Creates a textbox element
     *
     * @param string $uName       name of the element
     * @param mixed  $uValue      default value
     * @param array  $uAttributes set of the tag attributes
     *
     * @return string html output
     */
    public static function textBox($uName, $uValue = "", array $uAttributes = [])
    {
        $uAttributes["name"] = $uName;
        $uAttributes["value"] = $uValue;

        $tOutput = "<input type=\"text\" " . self::attributes($uAttributes) . " />";

        return $tOutput;
    }

    /**
     * Creates a checkbox element
     *
     * @param string $uName         name of the element
     * @param mixed  $uValue        value
     * @param mixed  $uCurrentValue default value
     * @param string $uText         caption
     * @param array  $uAttributes   set of the tag attributes
     *
     * @return string html output
     */
    public static function checkBox($uName, $uValue, $uCurrentValue = null, $uText = null, array $uAttributes = [])
    {
        $uAttributes["name"] = $uName;
        $uAttributes["value"] = $uValue;

        if ($uCurrentValue === $uValue) {
            $uAttributes["checked"] = "checked";
        }

        $tOutput = "<label><input type=\"checkbox\" " . self::attributes($uAttributes) . " />";

        if ($uText !== null) {
            $tOutput .= $uText;
        }

        $tOutput .= "</label>";

        return $tOutput;
    }

    /**
     * Returns doctype header to be printed out
     *
     * @param string $uType the type of the document
     *
     * @return bool|string html output
     */
    public static function doctype($uType = "html5")
    {
        if ($uType === "html5" || $uType === "xhtml5") {
            return "<!DOCTYPE html>" . PHP_EOL;
        } elseif ($uType ===  "xhtml11" || $uType === "xhtml1.1") {
            return
                "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" " .
                "\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">" .
                PHP_EOL;
        } elseif ($uType === "xhtml1" || $uType === "xhtml1-strict") {
            return
                "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" " .
                "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">" .
                PHP_EOL;
        } elseif ($uType === "xhtml1-trans") {
            return
                "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" " .
                "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">" .
                PHP_EOL;
        } elseif ($uType === "xhtml1-frame") {
            return
                "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Frameset//EN\" " .
                "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd\">" .
                PHP_EOL;
        } elseif ($uType === "html4-strict") {
            return
                "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" " .
                "\"http://www.w3.org/TR/html4/strict.dtd\">" .
                PHP_EOL;
        } elseif ($uType === "html4" || $uType === "html4-trans") {
            return
                "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" " .
                "\"http://www.w3.org/TR/html4/loose.dtd\">" .
                PHP_EOL;
        } elseif ($uType === "html4-frame") {
            return
                "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\" " .
                "\"http://www.w3.org/TR/html4/frameset.dtd\">" .
                PHP_EOL;
        }

        return false;
    }

    /**
     * Creates a script element
     *
     * @param string $uHref       hypertext reference of script file
     * @param array  $uAttributes set of the tag attributes
     *
     * @return string html output
     */
    public static function script($uHref, array $uAttributes = [])
    {
        $uAttributes["src"] = $uHref;

        $tOutput = "<script " . self::attributes($uAttributes) . "></script>";

        return $tOutput;
    }

    /**
     * Creates an inline script element
     *
     * @param string $uScriptContent  script content
     * @param array  $uAttributes     set of the tag attributes
     *
     * @return string html output
     */
    public static function scriptInline($uScriptContent, array $uAttributes = [])
    {
        $uAttributes["src"] = $uHref;

        $tOutput = "<script " . self::attributes($uAttributes) . ">{$uScriptContent}</script>";

        return $tOutput;
    }

    /**
     * Creates a link element
     *
     * @param string $uRelation   relation type
     * @param string $uHref       hypertext reference of linked file
     * @param array  $uAttributes set of the tag attributes
     *
     * @return string html output
     */
    public static function link($uRelation, $uHref, array $uAttributes = [])
    {
        $uAttributes["rel"] = $uRelation;
        $uAttributes["href"] = $uHref;

        $tOutput = "<link " . self::attributes($uAttributes) . " />";

        return $tOutput;
    }

    /**
     * Creates a link element to link a stylesheet file
     *
     * @param string $uHref       hypertext reference of linked file
     * @param string $uMediaType  target media type
     * @param array  $uAttributes set of the tag attributes
     *
     * @return string html output
     */
    public static function linkStyleSheet($uHref, $uMediaType = "all", array $uAttributes = [])
    {
        $uAttributes["rel"] = "stylesheet";
        $uAttributes["media"] = $uMediaType;
        $uAttributes["href"] = $uHref;

        $tOutput = "<link " . self::attributes($uAttributes) . " />";

        return $tOutput;
    }

    /**
     * Creates a pager widget
     *
     * @param int   $uCurrent   current page
     * @param int   $uTotal     total number of pages
     * @param array $uVariables variables
     *
     * @return string html output
     */
    public static function pager($uCurrent, $uTotal, array $uVariables)
    {
        $uVariables = $uVariables + self::$defaults;
        $tPages = ceil($uTotal / $uVariables["pager_pagesize"]);

        if (!isset($uVariables["link"])) {
            $uVariables["link"] = "<a href=\"{root}/{page}\" class=\"pagerlink\">{pagetext}</a>";
        }

        if (!isset($uVariables["passivelink"])) {
            $uVariables["passivelink"] = $uVariables["link"];
        }

        if (!isset($uVariables["activelink"])) {
            $uVariables["activelink"] = $uVariables["passivelink"];
        }

        if ($uCurrent <= 0) {
            $uCurrent = 1;
        } elseif ($uCurrent > $uTotal) {
            $uCurrent = $uTotal;
        }

        $tHalf = floor($uVariables["pager_numlinks"] * 0.5);
        $tStart = $uCurrent - $tHalf;
        $tEnd = $uCurrent + $tHalf - 1;

        if ($tStart < 1) {
            $tEnd += abs($tStart) + 1;
            $tStart = 1;
        }

        if ($tEnd > $tPages) {
            if ($tStart - $tEnd - $tPages > 0) {
                $tStart -= $tEnd - $tPages;
            }
            $tEnd = $tPages;
        }

        $tResult = "";

        if ($tPages > 1) {
            if ($uCurrent <= 1) {
                if ($uVariables["pager_firstlast"]) {
                    $tResult .= String::format(
                        $uVariables["passivelink"],
                        [
                            "page" => "1",
                            "pagetext" => $uVariables["pager_first"]
                        ]
                    );
                }
                $tResult .= String::format(
                    $uVariables["passivelink"],
                    [
                        "page" => "1",
                        "pagetext" => $uVariables["pager_prev"]
                    ]
                );
            } else {
                if ($uVariables["pager_firstlast"]) {
                    $tResult .= String::format(
                        $uVariables["link"],
                        [
                            "page" => "1",
                            "pagetext" => $uVariables["pager_first"]
                        ]
                    );
                }
                $tResult .= String::format(
                    $uVariables["link"],
                    [
                        "page" => $uCurrent - 1,
                        "pagetext" => $uVariables["pager_prev"]
                    ]
                );
            }

            if ($tStart > 1) {
                $tResult .= $uVariables["pager_dots"];
            } else {
                $tResult .= $uVariables["pager_divider"];
            }
        }

        for ($i = $tStart; $i <= $tEnd; $i++) {
            if ($uCurrent === $i) {
                $tResult .= String::format(
                    $uVariables["activelink"],
                    [
                        "page" => $i,
                        "pagetext" => $i
                    ]
                );
            } else {
                $tResult .= String::format(
                    $uVariables["link"],
                    [
                        "page" => $i,
                        "pagetext" => $i
                    ]
                );
            }

            if ($i !== $tEnd) {
                $tResult .= $uVariables["pager_divider"];
            }
        }

        if ($tPages > 1) {
            if ($tEnd < $tPages) {
                $tResult .= $uVariables["pager_dots"];
            } else {
                $tResult .= $uVariables["pager_divider"];
            }

            if ($uCurrent >= $tPages) {
                $tResult .= String::format(
                    $uVariables["passivelink"],
                    [
                        "page" => $tPages,
                        "pagetext" => $uVariables["pager_next"]
                    ]
                );
                if ($uVariables["pager_firstlast"]) {
                    $tResult .= String::format(
                        $uVariables["passivelink"],
                        [
                            "page" => $tPages,
                            "pagetext" => $uVariables["pager_last"]
                        ]
                    );
                }
            } else {
                $tResult .= String::format(
                    $uVariables["link"],
                    [
                        "page" => $uCurrent + 1,
                        "pagetext" => $uVariables["pager_next"]
                    ]
                );
                if ($uVariables["pager_firstlast"]) {
                    $tResult .= String::format(
                        $uVariables["link"],
                        [
                            "page" => $tPages,
                            "pagetext" => $uVariables["pager_last"]
                        ]
                    );
                }
            }
        }

        return $tResult;
    }

    /**
     * Creates a table widget
     *
     * @param array    $uHeaders   table headers
     * @param iterable $uData      table data
     * @param array    $uVariables variables
     *
     * @return string html output
     */
    public static function table(array $uHeaders, $uData, array $uVariables)
    {
        $uVariables = $uVariables + self::$defaults;
        $tResult = String::format($uVariables["table_table"], []);

        if (count($uHeaders) > 0) {
            $tResult .= "<tr>";
            foreach ($uHeaders as $tColumn) {
                $tResult .= String::format($uVariables["table_header"], ["value" => $tColumn]);
            }
            $tResult .= "</tr>";
        }

        $tCount = 0;
        foreach ($uData as $tRow) {
            if (isset($uVariables["table_row_func"])) {
                $tResult .= call_user_func($uVariables["table_row_func"], $tRow, $tCount++);
            } else {
                if (isset($uVariables["row"])) {
                    $tResult .= String::format($uVariables["table_row"], $tRow);
                } else {
                    $tResult .= "<tr>";

                    foreach ($tRow as $tColumn) {
                        $tResult .= String::format($uVariables["table_cell"], ["value" => $tColumn]);
                    }

                    $tResult .= "</tr>";
                }
            }
        }

        $tResult .= "</table>";

        return $tResult;
    }
}
