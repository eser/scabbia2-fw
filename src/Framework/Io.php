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

use Scabbia\Framework\Core;

/**
 * Io functionality for framework
 *
 * @package     Scabbia\Framework
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 */
class Io
{
    /**
     * Default variables for Io functionality
     *
     * @type array $defaults array of default variables
     */
    public static $defaults = [
        "pathSeparator" => DIRECTORY_SEPARATOR,
        "fileReadBuffer" => 4096
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
     * Encrypts the plaintext with the given key
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
     * Decrypts the ciphertext with the given key
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
     * Reads from a file
     *
     * @param string    $uPath  the file path
     * @param int       $uFlags io flags
     *
     * @return bool|string the file content
     */
    public static function read($uPath, $uFlags = LOCK_SH)
    {
        $tFullPath = Core::translateVariables($uPath);
        $tHandle = fopen($tFullPath, "r", false);
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
     * Writes to a file
     *
     * @param string    $uPath      the file path
     * @param string    $uContent   the file content
     * @param int       $uFlags     io flags
     *
     * @return bool
     */
    public static function write($uPath, $uContent, $uFlags = LOCK_EX)
    {
        $tFullPath = Core::translateVariables($uPath);
        $tHandle = fopen(
            $tFullPath,
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
     * Reads from a serialized file
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
     * Serializes an object into a file
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
     * Exports an object into a php file
     *
     * @param string        $uPath      the file path
     * @param string        $uContent   the file content
     *
     * @return bool
     */
    public static function writePhpFile($uPath, $uContent)
    {
        return self::write(
            $uPath,
            "<" . "?php return " . var_export($uContent, true) . ";\n"
        );
    }

    /**
     * Checks the path contains invalid chars or not
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
     * Checks the path is path rooted or not
     *
     * @param string $uPath the path
     *
     * @throws \Exception if path contains invalid chars
     * @return bool true if the path is rooted
     */
    public static function isPathRooted($uPath)
    {
        $tFullPath = Core::translateVariables($uPath);

        if (!self::checkInvalidPathChars($tFullPath)) {
            // TODO exception
            throw new \Exception("");
        }

        $tLength = strlen($tFullPath);
        if (strncasecmp(PHP_OS, "WIN", 3) === 0) {
            if (($tLength >= 1 && ($tFullPath[0] === "\\" || $tFullPath[0] === "/")) ||
                ($tLength >= 2 && $tFullPath[1] === ":")) {
                return true;
            }
        } else {
            if ($tLength >= 1 && $tFullPath[0] === "/") {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks the path is path relative or not
     *
     * @param string $uPath the path
     *
     * @throws \Exception if path contains invalid chars
     * @return bool true if the path is relative
     */
    public static function isPathRelative($uPath)
    {
        $tFullPath = Core::translateVariables($uPath);

        if (!self::checkInvalidPathChars($tFullPath)) {
            // TODO exception
            throw new \Exception("");
        }

        $tLength = strlen($tFullPath);
        if (strncasecmp(PHP_OS, "WIN", 3) === 0) {
            if (strncasecmp($tFullPath, "\\\\", 2) === 0) {
                return false;
            }

            if ($tLength >= 3 && ctype_alpha($tFullPath[0]) && $tFullPath[1] === ":" &&
                ($tFullPath[2] === "\\" || $tFullPath[2] === "/")) {
                return false;
            }
        } else {
            if ($tLength >= 1 && $tFullPath[0] === "/") {
                return false;
            }
        }

        return true;
    }

    /**
     * Combines given paths into a single path string
     *
     * @return null|string combined path
     */
    public static function combinePaths()
    {
        $tCombinedPath = null;
        $tTrimChars = (strncasecmp(PHP_OS, "WIN", 3) === 0) ? "\\/" : "/";

        for ($tPaths = func_get_args(), $i = count($tPaths) - 1; $i >= 0; $i--) {
            $tPath = Core::translateVariables($tPaths[$i]);

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
     * Gets the number of lines of given file
     *
     * @param string $uPath the path
     *
     * @return int|bool line count
     */
    public static function getFileLineCount($uPath)
    {
        $tFullPath = Core::translateVariables($uPath);
        $tLineCount = 1;

        // FIXME don't use silence operator
        $tFileHandle = @fopen($tFullPath, "r");
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
     * Determines the file is if readable and not expired
     *
     * @param string $uPath    the relative path
     * @param array  $uOptions options
     *
     * @return bool the result
     */
    public static function isReadable($uPath, array $uOptions = [])
    {
        $tFullPath = Core::translateVariables($uPath);
        if (!file_exists($tFullPath)) {
            return false;
        }

        $tLastMod = filemtime($tFullPath);
        if (isset($uOptions["ttl"]) && time() - $tLastMod > $uOptions["ttl"]) {
            return false;
        }

        if (isset($uOptions["newerthan"]) && $tLastMod >= $uOptions["newerthan"]) {
            return false;
        }

        return true;
    }

    /**
     * Reads the contents from cache file as long as it is not expired
     * If the file is expired, invokes callback method and caches output
     *
     * @param string      $uPath         the relative path
     * @param mixed       $uDefaultValue the default value
     * @param array       $uOptions      options
     *
     * @return mixed the result
     */
    public static function readFromCacheFile($uPath, $uDefaultValue, array $uOptions = [])
    {
        if (self::isReadable($uPath, $uOptions)) {
            return self::readSerialize($uPath);
        }

        if (is_a($uDefaultValue, "Closure")) {
            $uDefaultValue = call_user_func($uDefaultValue);
        }

        self::writeSerialize($uPath, $uDefaultValue);
        return $uDefaultValue;
    }

    /**
     * Reads the contents from cache folder as long as it is not expired
     * If the cached content is expired, invokes callback method and caches output
     *
     * @param string      $uPath         the relative path
     * @param mixed       $uDefaultValue the default value
     * @param array       $uOptions      options
     *
     * @return mixed the result
     */
    public static function readFromCache($uPath, $uDefaultValue, array $uOptions = [])
    {
        // FIXME is it really necessary?
        $tFullPath = Core::translateVariables($uPath);

        $tCachePath = Core::$basepath . "/writable/cache/" . crc32(realpath($tFullPath));

        if (self::isReadable($tCachePath, $uOptions)) {
            return self::readSerialize($tCachePath);
        }

        if (is_a($uDefaultValue, "Closure")) {
            $uDefaultValue = call_user_func($uDefaultValue);
        }

        self::writeSerialize($tCachePath, $uDefaultValue);

        return $uDefaultValue;
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
        $tFullPath = Core::translateVariables($uPath);

        $tArray = ["." => []];
        $tDir = new \DirectoryIterator($tFullPath);

        foreach ($tDir as $tFile) {
            $tFileName = $tFile->getFilename();

            if ($tFileName[0] === ".") { // $tFile->isDot()
                continue;
            }

            if ($tFile->isDir()) {
                if ($uRecursive) {
                    $tArray[$tFileName] = self::getFiles("{$tFullPath}/{$tFileName}", $uPattern, true, $uBasenames);
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
     * Garbage collects the given path
     *
     * @param string    $uPath  path
     * @param null|int  $uTtl   age
     *
     * @return void
     */
    public static function garbageCollect($uPath, $uTtl = null)
    {
        $tFullPath = Core::translateVariables($uPath);
        $tDirectory = new \DirectoryIterator($tFullPath);

        clearstatcache();
        foreach ($tDirectory as $tFile) {
            if (!$tFile->isFile()) {
                continue;
            }

            if ($uTtl !== null && (time() - $tFile->getMTime()) < $uTtl) {
                continue;
            }

            unlink($tFile->getPathname());
        }
    }

    /**
     * Apply a function/method to every file matching the given pattern
     *
     * @param string        $uPath         path to be searched
     * @param string|null   $uPattern      pattern of files will be in the list
     * @param bool          $uRecursive    recursive search
     * @param callable      $uCallback     callback function/method
     * @param mixed         $uStateObject  parameters will be passed to function
     *
     * @return void
     */
    public static function getFilesWalk($uPath, $uPattern, $uRecursive, /* callable */ $uCallback, $uStateObject = null)
    {
        $tFullPath = Core::translateVariables($uPath);
        $tDir = new \DirectoryIterator($tFullPath);

        foreach ($tDir as $tFile) {
            $tFileName = $tFile->getFilename();

            if ($tFileName[0] === ".") { // $tFile->isDot()
                continue;
            }

            if ($uRecursive && $tFile->isDir()) {
                self::getFilesWalk("{$tFullPath}/{$tFileName}", $uPattern, true, $uCallback, $uStateObject);
                continue;
            }

            if ($tFile->isFile() && ($uPattern === null || fnmatch($uPattern, $tFileName))) {
                call_user_func($uCallback, "{$tFullPath}/{$tFileName}", $uStateObject);
            }
        }
    }
}
