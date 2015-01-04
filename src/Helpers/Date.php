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
 * A bunch of utility methods for date and time operations
 *
 * @package     Scabbia\Helpers
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 *
 * @scabbia-compile
 *
 * @todo improve humanize (fuzzy span)
 */
class Date
{
    /**
     * Default variables for Date utility set
     *
     * @type array $defaults array of default variables
     */
    public static $defaults = [
        "short_date" => "d.m.Y",
        "short_time" => "H:i",
        "short_time_with_seconds" => "H:i:s",
        "short_datetime" => "d.m.Y H:i",
        "short_datetime_with_seconds" => "d.m.Y H:i:s",

        "seconds" => "seconds",
        "minutes" => "minutes",
        "hours" => "hours",
        "days" => "days",
        "weeks" => "weeks",
        "months" => "months",

        "yesterday" => "Yesterday",
        "today" => "Today",
        "tomorrow" => "Tomorrow",

        "now" => "Now"
    ];


    /**
     * Constructor to prevent new instances of Date class
     *
     * @return Date
     */
    final private function __construct()
    {
    }

    /**
     * Clone method to prevent duplication of Date class
     *
     * @return Date
     */
    final private function __clone()
    {
    }

    /**
     * Unserialization method to prevent restoration of Date class
     *
     * @return Date
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
     * Timestamp of beginning of the day
     *
     * @param int $uTimestamp timestamp
     *
     * @return int timestamp of beginning of the day
     */
    public static function beginningOfDay($uTimestamp)
    {
        return mktime(0, 0, 0, date("m", $uTimestamp), date("d", $uTimestamp), date("Y", $uTimestamp));
    }

    /**
     * Transforms given period into a time span
     *
     * @param int  $uPeriod period
     * @param bool $uNoDays return null if it passed a day
     *
     * @return null|array value and unit
     */
    public static function ago($uPeriod, $uNoDays = false)
    {
        if (!$uNoDays) {
            if ($uPeriod >= 2592000) {
                return [ceil($uPeriod / 2592000), self::$constant["months"]];
            }

            if ($uPeriod >= 604800) {
                return [ceil($uPeriod / 604800), self::$constant["weeks"]];
            }

            if ($uPeriod >= 86400) {
                return [ceil($uPeriod / 86400), self::$constant["days"]];
            }
        } elseif ($uPeriod >= 86400) {
            return null;
        }

        if ($uPeriod >= 3600) {
            return [ceil($uPeriod / 3600), self::$constant["hours"]];
        }

        if ($uPeriod >= 60) {
            return [ceil($uPeriod / 60), self::$constant["minutes"]];
        }

        if ($uPeriod > 0) {
            return [ceil($uPeriod), self::$constant["seconds"]];
        }

        return null;
    }

    /**
     * Transforms a timestamp to human-readable format
     *
     * @param int  $uTimestamp           timestamp
     * @param int  $uConstantTimestamp   constant timestamp
     * @param bool $uCalculateAgo        uses ago method for past
     * @param bool $uShowHours           includes hour and minute
     *
     * @return string output
     */
    public static function humanize($uTimestamp, $uConstantTimestamp, $uCalculateAgo = true, $uShowHours = true)
    {
        if (($tDifference = $uConstantTimestamp - $uTimestamp) >= 0 && $uCalculateAgo) {
            $tAgo = self::ago($tDifference, true);

            if ($tAgo !== null) {
                return implode(" ", $tAgo);
            }
        }

        if ($tDifference >= 86400) {
            if ($uShowHours) {
                return self::$defaults["yesterday"] . ", " . date(self::$defaults["short_time"], $uTimestamp);
            }

            return self::$defaults["yesterday"];
        }

        if ($tDifference > 0) {
            if ($uShowHours) {
                return self::$defaults["today"] . ", " . date(self::$defaults["short_time"], $uTimestamp);
            }

            return self::$defaults["today"];
        }

        if ($tDifference === 0) {
            return self::$defaults["now"];
        }

        if ($tDifference >= -86400) {
            if ($uShowHours) {
                return self::$defaults["tomorrow"] . ", " . date(self::$defaults["short_time"], $uTimestamp);
            }

            return self::$defaults["tomorrow"];
        }

        if ($uShowHours) {
            return date(self::$defaults["short_datetime"], $uTimestamp);
        }

        return date(self::$defaults["short_date"], $uTimestamp);
    }

    /**
     * Transforms a timestamp to GMT format
     *
     * @param int  $uTimestamp timestamp
     * @param bool $uAddSuffix adds GMT at the end of the output
     *
     * @return string output
     */
    public static function toGmt($uTimestamp, $uAddSuffix = true)
    {
        if ($uAddSuffix) {
            return gmdate("D, d M Y H:i:s", $uTimestamp) . " GMT";
        }

        return gmdate("D, d M Y H:i:s", $uTimestamp);
    }

    /**
     * Transforms a timestamp to DOS format
     *
     * @param int  $uTimestamp timestamp
     *
     * @return string output
     */
    public static function toDos($uTimestamp)
    {
        $tTimeArray = getdate($uTimestamp);

        if ($tTimeArray["year"] < 1980) {
            $tTimeArray["year"] = 1980;
            $tTimeArray["mon"] = 1;
            $tTimeArray["mday"] = 1;
            $tTimeArray["hours"] = 0;
            $tTimeArray["minutes"] = 0;
            $tTimeArray["seconds"] = 0;
        }

        // 4byte: hi=date, lo=time
        return (($tTimeArray["year"] - 1980) << 25) |
        ($tTimeArray["mon"] << 21) |
        ($tTimeArray["mday"] << 16) |
        ($tTimeArray["hours"] << 11) |
        ($tTimeArray["minutes"] << 5) |
        ($tTimeArray["seconds"] >> 1);
    }

    /**
     * Transforms a timestamp from GMT format
     *
     * @param int  $uTimestamp timestamp
     *
     * @return string output
     */
    public static function fromDos($uTimestamp)
    {
        return mktime(
            ($uTimestamp >> 11) & 0x1f,
            ($uTimestamp >> 5) & 0x3f,
            2 * ($uTimestamp & 0x1f),
            ($uTimestamp >> 21) & 0x0f,
            ($uTimestamp >> 16) & 0x1f,
            (($uTimestamp >> 25) & 0x7f) + 1980
        );
    }

    /**
     * Transforms a timestamp to database format
     *
     * @param int    $uTimestamp timestamp
     * @param string $uFormat    destination format
     *
     * @return string output
     */
    public static function toDb($uTimestamp, $uFormat = "d-m-Y H:i:s")
    {
        if (!is_numeric($uTimestamp)) {
            $tTime = date_parse_from_format($uFormat, $uTimestamp);
            $uTimestamp = mktime(
                $tTime["hour"],
                $tTime["minute"],
                $tTime["second"],
                $tTime["month"],
                $tTime["day"],
                $tTime["year"]
            ); // $tTime["is_dst"]
        }

        return date("Y-m-d H:i:s", $uTimestamp);
    }

    /**
     * Transforms a timestamp from database format
     *
     * @param int    $uTimestamp timestamp
     * @param string $uFormat    source format
     *
     * @return string output
     */
    public static function fromDb($uTimestamp, $uFormat = "d-m-Y H:i:s")
    {
        $tTime = date_parse_from_format($uFormat, $uTimestamp);

        return mktime(
            $tTime["hour"],
            $tTime["minute"],
            $tTime["second"],
            $tTime["month"],
            $tTime["day"],
            $tTime["year"]
        ); // $tTime["is_dst"]
    }

    /**
     * Transforms a timestamp into another format
     *
     * @param int    $uTimestamp         timestamp
     * @param string $uSourceFormat      source format
     * @param string $uDestinationFormat destination format
     *
     * @return string output
     */
    public static function convert($uTimestamp, $uSourceFormat, $uDestinationFormat)
    {
        $tTime = date_parse_from_format($uSourceFormat, $uTimestamp);
        $tTimestamp = mktime(
            $tTime["hour"],
            $tTime["minute"],
            $tTime["second"],
            $tTime["month"],
            $tTime["day"],
            $tTime["year"]
        ); // $tTime["is_dst"]

        return date($uDestinationFormat, $tTimestamp);
    }
}
