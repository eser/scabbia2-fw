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
        "pathSeparator" => DIRECTORY_SEPARATOR,
        "fileReadBuffer" => 4096
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
     * Encrypts the plaintext with the given key.
     *
     * @param string    $uString    the plaintext
     * @param string    $uKey       the key
     *
     * @return string   ciphertext
     */
    public static function encrypt($uString, $uKey)
    {
        $tResult = "";

        for ($i = 1, $tCount = strlen($uString); $i <= $tCount; $i++) {
            $tChar = substr($uString, $i - 1, 1);
            $tKeyChar = substr($uKey, ($i % strlen($uKey)) - 1, 1);
            $tResult .= chr(ord($tChar) + ord($tKeyChar));
        }

        return $tResult;
    }

    /**
     * Decrypts the ciphertext with the given key.
     *
     * @param string    $uString    the ciphertext
     * @param string    $uKey       the key
     *
     * @return string   plaintext
     */
    public static function decrypt($uString, $uKey)
    {
        $tResult = "";

        for ($i = 1, $tCount = strlen($uString); $i <= $tCount; $i++) {
            $tChar = substr($uString, $i - 1, 1);
            $tKeyChar = substr($uKey, ($i % strlen($uKey)) - 1, 1);
            $tResult .= chr(ord($tChar) - ord($tKeyChar));
        }

        return $tResult;
    }

    /**
     * Reads from a file.
     *
     * @param string    $uPath  the file path
     * @param int       $uFlags io flags
     *
     * @return bool|string the file content
     */
    public static function read($uPath, $uFlags = LOCK_SH)
    {
        $tHandle = fopen($uPath, "r", false);
        if ($tHandle === false) {
            return false;
        }

        $tLock = flock($tHandle, $uFlags);
        if ($tLock === false) {
            fclose($tHandle);

            return false;
        }

        $tContent = stream_get_contents($tHandle);
        flock($tHandle, LOCK_UN);
        fclose($tHandle);

        return $tContent;
    }

    /**
     * Writes to a file.
     *
     * @param string    $uPath      the file path
     * @param string    $uContent   the file content
     * @param int       $uFlags     io flags
     *
     * @return bool
     */
    public static function write($uPath, $uContent, $uFlags = LOCK_EX)
    {
        $tHandle = fopen(
            $uPath,
            ($uFlags & FILE_APPEND) > 0 ? "a" : "w",
            false
        );
        if ($tHandle === false) {
            return false;
        }

        if (flock($tHandle, $uFlags) === false) {
            fclose($tHandle);

            return false;
        }

        fwrite($tHandle, $uContent);
        fflush($tHandle);
        flock($tHandle, LOCK_UN);
        fclose($tHandle);

        return true;
    }

    /**
     * Reads from a serialized file.
     *
     * @param string        $uPath      the file path
     * @param string|null   $uKeyphase  the key
     *
     * @return bool|mixed   the unserialized object
     */
    public static function readSerialize($uPath, $uKeyphase = null)
    {
        $tContent = self::read($uPath);

        //! ambiguous return value
        if ($tContent === false) {
            return false;
        }

        if ($uKeyphase !== null && strlen($uKeyphase) > 0) {
            $tContent = self::decrypt($tContent, $uKeyphase);
        }

        return unserialize($tContent);
    }

    /**
     * Serializes an object into a file.
     *
     * @param string        $uPath      the file path
     * @param string        $uContent   the file content
     * @param string|null   $uKeyphase  the key
     *
     * @return bool
     */
    public static function writeSerialize($uPath, $uContent, $uKeyphase = null)
    {
        $tContent = serialize($uContent);

        if ($uKeyphase !== null && strlen($uKeyphase) > 0) {
            $tContent = self::encrypt($tContent, $uKeyphase);
        }

        return self::write($uPath, $tContent);
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
            } elseif (strpos($tTrimChars, $tPath[$tPathLength - 1]) === false) {
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

    /**
     * Gets the number of lines of given file.
     *
     * @param string $uPath the path
     *
     * @return int|bool line count
     */
    public static function getFileLineCount($uPath)
    {
        $tLineCount = 1;

        $tFileHandle = @fopen($uPath, "r");
        if ($tFileHandle === false) {
            return false;
        }

        while (!feof($tFileHandle)) {
            $tLineCount += substr_count(fgets($tFileHandle, self::$defaults["fileReadBuffer"]), "\n");
        }

        fclose($tFileHandle);

        return $tLineCount;
    }

    /**
     * Determines the file is if readable and not expired.
     *
     * @param string    $uPath  the relative path
     * @param int|bool  $uTtl   the time to live period in seconds
     *
     * @return bool the result
     */
    public static function isReadable($uPath, $uTtl = false)
    {
        if (!file_exists($uPath)) {
            return false;
        }

        return ($uTtl === false || (time() - filemtime($uPath) <= $uTtl));
    }

    /**
     * Determines the file is if readable and newer than given timestamp.
     *
     * @param string    $uPath          the relative path
     * @param int       $uLastModified  the time to live period in seconds
     *
     * @return bool the result
     */
    public static function isReadableAndNewerThan($uPath, $uLastModified)
    {
        return (file_exists($uPath) && filemtime($uPath) >= $uLastModified);
    }

    /**
     * Reads the contents from cache file as long as it is not expired.
     * If the file is expired, invokes callback method and caches output.
     *
     * @param string      $uPath         the relative path
     * @param mixed       $uDefaultValue the default value
     * @param int|bool    $uTtl          the time to live period in seconds
     *
     * @return mixed the result
     */
    public static function readFromCache($uPath, $uDefaultValue, $uTtl = false)
    {
        if (self::isReadable($uPath, $uTtl)) {
            return self::readSerialize($uPath);
        }

        if (is_a($uDefaultValue, "Closure")) {
            $uDefaultValue = call_user_func($uDefaultValue);
        }

        self::writeSerialize($uPath, $uDefaultValue);
        return $uDefaultValue;
    }

    /**
     * Garbage collects the given path
     *
     * @param string    $uPath  path
     * @param int       $uTtl   age
     */
    public static function garbageCollect($uPath, $uTtl = -1)
    {
        $tDirectory = new \DirectoryIterator($uPath);

        clearstatcache();
        foreach ($tDirectory as $tFile) {
            if (!$tFile->isFile()) {
                continue;
            }

            if ($uTtl !== -1 && (time() - $tFile->getMTime()) < $uTtl) {
                continue;
            }

            unlink($tFile->getPathname());
        }
    }

    /**
     * Gets the list of files matching the given pattern
     *
     * @param string        $uPath       path to be searched
     * @param string|null   $uPattern    pattern of files will be in the list
     * @param bool          $uRecursive  recursive search
     * @param bool          $uBasenames  use basenames only
     *
     * @return array the list of files
     */
    public static function getFiles($uPath, $uPattern = null, $uRecursive = true, $uBasenames = false)
    {
        $tArray = ["." => []];
        $tDir = new \DirectoryIterator($uPath);

        foreach ($tDir as $tFile) {
            $tFileName = $tFile->getFilename();

            if ($tFileName[0] === ".") { // $tFile->isDot()
                continue;
            }

            if ($tFile->isDir()) {
                if ($uRecursive) {
                    $tArray[$tFileName] = self::getFiles("{$uPath}/{$tFileName}", $uPattern, true, $uBasenames);
                    continue;
                }

                $tArray[$tFileName] = null;
                continue;
            }

            if ($tFile->isFile() && ($uPattern === null || fnmatch($uPattern, $tFileName))) {
                if ($uBasenames) {
                    $tArray["."][] = pathinfo($tFileName, PATHINFO_FILENAME);
                } else {
                    $tArray["."][] = $tFileName;
                }
            }
        }

        return $tArray;
    }


    /**
     * Apply a function/method to every file matching the given pattern
     *
     * @param string        $uPath       path to be searched
     * @param string|null   $uPattern    pattern of files will be in the list
     * @param bool          $uRecursive  recursive search
     * @param callable      $uCallback   callback function/method
     */
    public static function getFilesWalk($uPath, $uPattern, $uRecursive, /* callable */ $uCallback)
    {
        $tDir = new \DirectoryIterator($uPath);

        foreach ($tDir as $tFile) {
            $tFileName = $tFile->getFilename();

            if ($tFileName[0] === ".") { // $tFile->isDot()
                continue;
            }

            if ($uRecursive && $tFile->isDir()) {
                self::getFilesWalk("{$uPath}/{$tFileName}", $uPattern, true, $uCallback);
                continue;
            }

            if ($tFile->isFile() && ($uPattern === null || fnmatch($uPattern, $tFileName))) {
                call_user_func($uCallback, "{$uPath}/{$tFileName}");
            }
        }
    }
}
