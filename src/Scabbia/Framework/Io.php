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
 */

namespace Scabbia\Framework;

/**
 * Io functionality for framework.
 *
 * @package     Scabbia\Framework
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 */
class Io
{
    /**
     * Default variables for Io functionality.
     *
     * @type array $defaults array of default variables
     */
    public static $defaults = [
        "pathSeparator" => DIRECTORY_SEPARATOR
    ];


    /**
     * Sets the default variables.
     *
     * @param array $uDefaults variables to be set
     */
    public static function setDefaults($uDefaults)
    {
        self::$defaults = $uDefaults + self::$defaults;
    }

    /**
     * Checks the path contains invalid chars or not.
     *
     * @param string $uPath the path
     *
     * @return bool true if the path contains invalid chars
     */
    public static function checkInvalidPathChars($uPath)
    {
        if (strncasecmp(PHP_OS, "WIN", 3) !== 0) {
            if (strncasecmp($uPath, "\\\\", 2) === 0) {
                return false;
            }
        }

        for ($i = strlen($uPath) - 1; $i >= 0; $i--) {
            if (ord($uPath[$i]) < 32 || $uPath[$i] === "<" || $uPath[$i] === ">" || $uPath[$i] === "|" ||
                $uPath[$i] === "\"") {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks the path is path rooted or not.
     *
     * @param string $uPath the path
     *
     * @throws \Exception if path contains invalid chars
     * @return bool true if the path is rooted
     */
    public static function isPathRooted($uPath)
    {
        if (!self::checkInvalidPathChars($uPath)) {
            // TODO exception
            throw new \Exception("");
        }

        $tLength = strlen($uPath);
        if (strncasecmp(PHP_OS, "WIN", 3) === 0) {
            if (($tLength >= 1 && ($uPath[0] === "\\" || $uPath[0] === "/")) || ($tLength >= 2 && $uPath[1] === ":")) {
                return true;
            }
        } else {
            if ($tLength >= 1 && $uPath[0] === "/") {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks the path is path relative or not.
     *
     * @param string $uPath the path
     *
     * @throws \Exception if path contains invalid chars
     * @return bool true if the path is relative
     */
    public static function isPathRelative($uPath)
    {
        if (!self::checkInvalidPathChars($uPath)) {
            // TODO exception
            throw new \Exception("");
        }

        $tLength = strlen($uPath);
        if (strncasecmp(PHP_OS, "WIN", 3) === 0) {
            if (strncasecmp($uPath, "\\\\", 2) === 0) {
                return false;
            }

            if ($tLength >= 3 && ctype_alpha($uPath[0]) && $uPath[1] === ":" &&
                ($uPath[2] === "\\" || $uPath[2] === "/")) {
                return false;
            }
        } else {
            if ($tLength >= 1 && $uPath[0] === "/") {
                return false;
            }
        }

        return true;
    }

    /**
     * Combines given paths into a single path string.
     *
     * @return null|string combined path
     */
    public static function combinePaths()
    {
        $tCombinedPath = null;
        $tTrimChars = (strncasecmp(PHP_OS, "WIN", 3) === 0) ? "\\/" : "/";

        for ($tPaths = func_get_args(), $i = count($tPaths) - 1; $i >= 0; $i--) {
            $tPath = $tPaths[$i];

            if (($tPathLength = strlen($tPath)) === 0) {
                continue;
            }

            if ($tCombinedPath === null) {
                $tCombinedPath = $tPath;
            } else if (strpos($tTrimChars, $tPath[$tPathLength - 1]) === false) {
                $tCombinedPath = $tPath . self::$defaults["pathSeparator"] . $tCombinedPath;
            } else {
                $tCombinedPath = $tPath . $tCombinedPath;
            }

            if (self::isPathRooted($tPath)) {
                break;
            }
        }

        return $tCombinedPath;
    }
}
