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

use DirectoryIterator;
use UnexpectedValueException;

/**
 * A bunch of utility methods for file system functionality
 *
 * @package     Scabbia\Helpers
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 *
 * #DISABLED# @scabbia-compile
 */
class FileSystem
{
    /** @type int NONE               no flag */
    const NONE = 0;
    /** @type int ENCRYPTION         use encryption */
    const ENCRYPTION = 1;
    /** @type int USE_JSON           use json for serialization */
    const USE_JSON = 2;
    /** @type int APPEND             append to file */
    const APPEND = 4;
    /** @type int LOCK_NONBLOCKING   lock file with nonblocking lock */
    const LOCK_NONBLOCKING = 8;
    /** @type int LOCK_SHARE         lock file with share lock */
    const LOCK_SHARE = 16;
    /** @type int LOCK_EXCLUSIVE     lock file with exclusive lock */
    const LOCK_EXCLUSIVE = 32;


    /**
     * Default variables for io functionality
     *
     * @type array $defaults array of default variables
     */
    public static $defaults = [
        "pathSeparator" => DIRECTORY_SEPARATOR,
        "fileReadBuffer" => 4096,
        "keyphase" => "scabbia_default"
    ];


    /**
     * Constructor to prevent new instances of FileSystem class
     *
     * @return FileSystem
     */
    final private function __construct()
    {
    }

    /**
     * Clone method to prevent duplication of FileSystem class
     *
     * @return FileSystem
     */
    final private function __clone()
    {
    }

    /**
     * Unserialization method to prevent restoration of FileSystem class
     *
     * @return FileSystem
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
     * @param string    $uPath      the file path
     * @param int       $uFlags     flags
     *
     * @return bool|string the file content
     */
    public static function read($uPath, $uFlags = self::LOCK_SHARE)
    {
        $tHandle = fopen($uPath, "r", false);
        if ($tHandle === false) {
            return false;
        }

        if (($uFlags & self::LOCK_NONBLOCKING) === self::LOCK_NONBLOCKING) {
            $tLockFlag = LOCK_NB;
        } elseif (($uFlags & self::LOCK_SHARE) === self::LOCK_SHARE) {
            $tLockFlag = LOCK_SH;
        } elseif (($uFlags & self::LOCK_EXCLUSIVE) === self::LOCK_EXCLUSIVE) {
            $tLockFlag = LOCK_EX;
        } else {
            $tLockFlag = false;
        }

        if ($tLockFlag !== false) {
            if (flock($tHandle, $tLockFlag) === false) {
                fclose($tHandle);

                return false;
            }
        }

        $tContent = stream_get_contents($tHandle);
        if ($tLockFlag !== false) {
            flock($tHandle, LOCK_UN);
        }

        fclose($tHandle);

        if (($uFlags & self::ENCRYPTION) === self::ENCRYPTION) {
            return self::decrypt($tContent, self::$defaults["keyphase"]);
        }

        return $tContent;
    }

    /**
     * Writes to a file
     *
     * @param string    $uPath      the file path
     * @param string    $uContent   the file content
     * @param int       $uFlags     flags
     *
     * @return bool
     */
    public static function write($uPath, $uContent, $uFlags = self::LOCK_EXCLUSIVE)
    {
        $tHandle = fopen(
            $uPath,
            (($uFlags & self::APPEND) === self::APPEND) ? "a" : "w",
            false
        );
        if ($tHandle === false) {
            return false;
        }

        if (($uFlags & self::LOCK_NONBLOCKING) === self::LOCK_NONBLOCKING) {
            $tLockFlag = LOCK_NB;
        } elseif (($uFlags & self::LOCK_SHARE) === self::LOCK_SHARE) {
            $tLockFlag = LOCK_SH;
        } elseif (($uFlags & self::LOCK_EXCLUSIVE) === self::LOCK_EXCLUSIVE) {
            $tLockFlag = LOCK_EX;
        } else {
            $tLockFlag = false;
        }

        if ($tLockFlag !== false) {
            if (flock($tHandle, $tLockFlag) === false) {
                fclose($tHandle);

                return false;
            }
        }

        if (($uFlags & self::ENCRYPTION) === self::ENCRYPTION) {
            fwrite($tHandle, self::encrypt($tContent, self::$defaults["keyphase"]));
        } else {
            fwrite($tHandle, $uContent);
        }

        fflush($tHandle);
        if ($tLockFlag !== false) {
            flock($tHandle, LOCK_UN);
        }

        fclose($tHandle);

        return true;
    }

    /**
     * Reads from a serialized file
     *
     * @param string    $uPath      the file path
     * @param int       $uFlags     flags
     *
     * @return bool|mixed   the unserialized object
     */
    public static function readSerialize($uPath, $uFlags = self::LOCK_SHARE)
    {
        $tContent = self::read($uPath, $uFlags);

        //! ambiguous return value
        if ($tContent === false) {
            return false;
        }

        if (($uFlags & self::USE_JSON) === self::USE_JSON) {
            return json_decode($tContent);
        }

        return unserialize($tContent);
    }

    /**
     * Serializes an object into a file
     *
     * @param string        $uPath      the file path
     * @param string        $uContent   the file content
     * @param int           $uFlags     flags
     *
     * @return bool
     */
    public static function writeSerialize($uPath, $uContent, $uFlags = self::LOCK_EXCLUSIVE)
    {
        if (($uFlags & self::USE_JSON) === self::USE_JSON) {
            return self::write($uPath, json_encode($uContent), $uFlags);
        }

        return self::write($uPath, serialize($uContent), $uFlags);
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
     * @throws UnexpectedValueException if path contains invalid chars
     * @return bool true if the path is rooted
     */
    public static function isPathRooted($uPath)
    {
        if (!self::checkInvalidPathChars($uPath)) {
            // TODO exception
            throw new UnexpectedValueException("");
        }

        $tLength = strlen($uPath);
        if (strncasecmp(PHP_OS, "WIN", 3) === 0) {
            if (($tLength >= 1 && ($uPath[0] === "\\" || $uPath[0] === "/")) ||
                ($tLength >= 2 && $uPath[1] === ":")) {
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
     * Checks the path is path relative or not
     *
     * @param string $uPath the path
     *
     * @throws UnexpectedValueException if path contains invalid chars
     * @return bool true if the path is relative
     */
    public static function isPathRelative($uPath)
    {
        if (!self::checkInvalidPathChars($uPath)) {
            // TODO exception
            throw new UnexpectedValueException("");
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
     * Combines given paths into a single path string
     *
     * @param array $uPaths paths
     *
     * @return null|string combined path
     */
    public static function combinePaths(...$uPaths)
    {
        $tCombinedPath = null;
        $tTrimChars = (strncasecmp(PHP_OS, "WIN", 3) === 0) ? "\\/" : "/";

        for ($i = count($uPaths) - 1; $i >= 0; $i--) {
            $tPath = $uPaths[$i];

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
        $tLineCount = 1;

        if (!is_readable($uPath) || ($tFileHandle = fopen($uPath, "r")) === false) {
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
        if (!file_exists($uPath)) {
            return false;
        }

        $tLastMod = filemtime($uPath);
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
        $tDir = new DirectoryIterator($uPath);

        foreach ($tDir as $tFile) {
            $tFileName = $tFile->getFilename();

            // $tFile->isDot()
            if ($tFileName[0] === ".") {
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
     * Garbage collects the given path
     *
     * @param string  $uPath    path
     * @param array   $uOptions options
     *
     * @return void
     */
    public static function garbageCollect($uPath, array $uOptions = [])
    {
        $tDirectory = new DirectoryIterator($uPath);

        clearstatcache();
        foreach ($tDirectory as $tFile) {
            if (!$tFile->isFile()) {
                continue;
            }

            $tFileName = $tFile->getFilename();

            if (isset($uOptions["dotFiles"]) && $uOptions["dotFiles"] === false && $tFileName[0] === ".") {
                // $tFile->isDot()
                continue;
            }

            $tLastMod = $tFile->getMTime();
            if (isset($uOptions["ttl"]) && time() - $tLastMod <= $uOptions["ttl"]) {
                continue;
            }

            if (isset($uOptions["newerthan"]) && $tLastMod < $uOptions["newerthan"]) {
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
        $tDir = new DirectoryIterator($uPath);

        foreach ($tDir as $tFile) {
            $tFileName = $tFile->getFilename();

            // $tFile->isDot()
            if ($tFileName[0] === ".") {
                continue;
            }

            if ($uRecursive && $tFile->isDir()) {
                self::getFilesWalk("{$uPath}/{$tFileName}", $uPattern, true, $uCallback, $uStateObject);
                continue;
            }

            if ($tFile->isFile() && ($uPattern === null || fnmatch($uPattern, $tFileName))) {
                call_user_func($uCallback, "{$uPath}/{$tFileName}", $uStateObject);
            }
        }
    }

    /**
     * Returns the mimetype of given extension
     *
     * @param string $uExtension extension of the file
     * @param string $uDefault   default mimetype if nothing found
     *
     * @return string
     */
    public static function getMimetype($uExtension, $uDefault = "application/octet-stream")
    {
        $tExtension = String::toLower($uExtension);

        if ($tExtension === "pdf") {
            return "application/pdf";
        } elseif ($tExtension === "exe") {
            return "application/octet-stream";
        } elseif ($tExtension === "dll") {
            return "application/x-msdownload";
        } elseif ($tExtension === "zip") {
            return "application/zip";
        } elseif ($tExtension === "rar") {
            return "application/x-rar-compressed";
        } elseif ($tExtension === "gz" || $tExtension === "gzip" || $tExtension === "tgz") {
            return "application/gzip";
        } elseif ($tExtension === "7z") {
            return "application/x-7z-compressed";
        } elseif ($tExtension === "tar") {
            return "application/x-tar";
        } elseif ($tExtension === "jar") {
            return "application/java-archive";
        } elseif ($tExtension === "deb") {
            return "application/x-deb";
        } elseif ($tExtension === "img") {
            return "application/x-apple-diskimage";
        } elseif ($tExtension === "csv") {
            return "text/csv";
        } elseif ($tExtension === "txt" || $tExtension === "text" || $tExtension === "log" || $tExtension === "ini") {
            return "text/plain";
        } elseif ($tExtension === "md") {
            return "text/x-markdown";
        } elseif ($tExtension === "rtf") {
            return "text/rtf";
        } elseif ($tExtension === "odt") {
            return "application/vnd.oasis.opendocument.text";
        } elseif ($tExtension === "ods") {
            return "application/vnd.oasis.opendocument.spreadsheet";
        } elseif ($tExtension === "smil") {
            return "application/smil";
        } elseif ($tExtension === "eml") {
            return "message/rfc822";
        } elseif ($tExtension === "xml" || $tExtension === "xsl") {
            return "text/xml";
        } elseif ($tExtension === "doc" || $tExtension === "dot" || $tExtension === "word") {
            return "application/msword";
        } elseif ($tExtension === "docx" || $tExtension === "dotx") {
            return "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
        } elseif ($tExtension === "xls") {
            return "application/vnd.ms-excel";
        } elseif ($tExtension === "xl") {
            return "application/excel";
        } elseif ($tExtension === "xlsx") {
            return "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
        } elseif ($tExtension === "ppt" || $tExtension === "pps") {
            return "application/vnd.ms-powerpoint";
        } elseif ($tExtension === "pptx") {
            return "application/vnd.openxmlformats-officedocument.presentationml.presentation";
        } elseif ($tExtension === "ppsx") {
            return "application/vnd.openxmlformats-officedocument.presentationml.slideshow";
        } elseif ($tExtension === "ics") {
            return "text/calendar";
        } elseif ($tExtension === "vcf" || $tExtension === "vcard") {
            return "text/vcard";
        } elseif ($tExtension === "bmp") {
            return "image/x-ms-bmp";
        } elseif ($tExtension === "gif") {
            return "image/gif";
        } elseif ($tExtension === "png") {
            return "image/png";
        } elseif ($tExtension === "jpeg" || $tExtension === "jpe" || $tExtension === "jpg") {
            return "image/jpeg";
        } elseif ($tExtension === "webp") {
            return "image/webp";
        } elseif ($tExtension === "tif" || $tExtension === "tiff") {
            return "image/tiff";
        } elseif ($tExtension === "psd") {
            return "image/vnd.adobe.photoshop";
        } elseif ($tExtension === "ai" || $tExtension === "eps" || $tExtension === "ps") {
            return "application/postscript";
        } elseif ($tExtension === "cdr") {
            return "application/cdr";
        } elseif ($tExtension === "mid" || $tExtension === "midi") {
            return "audio/midi";
        } elseif ($tExtension === "mpga" || $tExtension === "mp2" || $tExtension === "mp3") {
            return "audio/mpeg";
        } elseif ($tExtension === "aif" || $tExtension === "aiff" || $tExtension === "aifc") {
            return "audio/x-aiff";
        } elseif ($tExtension === "wav") {
            return "audio/x-wav";
        } elseif ($tExtension === "aac") {
            return "audio/aac";
        } elseif ($tExtension === "ogg") {
            return "application/ogg";
        } elseif ($tExtension === "wma") {
            return "audio/x-ms-wma";
        } elseif ($tExtension === "m4a") {
            return "audio/x-m4a";
        } elseif ($tExtension === "mpeg" || $tExtension === "mpg" || $tExtension === "mpe") {
            return "video/mpeg";
        } elseif ($tExtension === "mp4" || $tExtension === "f4v") {
            return "application/mp4";
        } elseif ($tExtension === "qt" || $tExtension === "mov") {
            return "video/quicktime";
        } elseif ($tExtension === "avi") {
            return "video/x-msvideo";
        } elseif ($tExtension === "wmv") {
            return "video/x-ms-wmv";
        } elseif ($tExtension === "webm") {
            return "video/webm";
        } elseif ($tExtension === "swf") {
            return "application/x-shockwave-flash";
        } elseif ($tExtension === "flv") {
            return "video/x-flv";
        } elseif ($tExtension === "mkv") {
            return "video/x-matroska";
        } elseif ($tExtension === "htm" || $tExtension === "html" || $tExtension === "shtm" ||
            $tExtension === "shtml") {
            return "text/html";
        } elseif ($tExtension === "php") {
            return "application/x-httpd-php";
        } elseif ($tExtension === "phps") {
            return "application/x-httpd-php-source";
        } elseif ($tExtension === "css") {
            return "text/css";
        } elseif ($tExtension === "js") {
            return "application/x-javascript";
        } elseif ($tExtension === "json") {
            return "application/json";
        } elseif ($tExtension === "c" || $tExtension === "h") {
            return "text/x-c";
        } elseif ($tExtension === "py") {
            return "application/x-python";
        } elseif ($tExtension === "sh") {
            return "text/x-shellscript";
        } elseif ($tExtension === "pem") {
            return "application/x-x509-user-cert";
        } elseif ($tExtension === "crt" || $tExtension === "cer") {
            return "application/x-x509-ca-cert";
        } elseif ($tExtension === "pgp") {
            return "application/pgp";
        } elseif ($tExtension === "gpg") {
            return "application/gpg-keys";
        } elseif ($tExtension === "svg") {
            return "image/svg+xml";
        } elseif ($tExtension === "ttf") {
            return "application/x-font-ttf:";
        } elseif ($tExtension === "woff") {
            return "application/x-font-woff";
        }

        if (function_exists("finfo_open")) {
            $tFinfo = finfo_open(FILEINFO_MIME);
            $tMimetype = finfo_file($tFinfo, "test.{$uExtension}");
            finfo_close($tFinfo);

            return $tMimetype;
        }

        return $uDefault;
    }
}
