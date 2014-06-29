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
 * Portions of this code are from Portable PHP password hashing framework under the public domain.
 *
 * (c) Solar Designer <solar@openwall.com>
 *
 * The homepage URL for this framework is:
 * http://www.openwall.com/phpass/
 *
 * Modifications made:
 * - Scabbia Framework code styles applied.
 */

namespace Scabbia\Security;

/**
 * Basic hash class which is originally named as Portable PHP password hashing framework
 * For bcrypt functionality, it is made a part of Scabbia\Security.
 *
 * @package     Scabbia\Security
 * @author      Solar Designer <solar@openwall.com>
 * @since       2.0.0
 */
class Hash
{
    /** @type mixed alphabet */
    public $itoa64 = "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    /** @type mixed iteration count log2 */
    public $iterationCountLog2;
    /** @type mixed portable hashes */
    public $portableHashes;
    /** @type mixed random state */
    public $randomState;


    /**
     * Initializes a Hash class instance
     *
     * @param int  $uIterationCountLog2 iteration count log2 (between 4 and 31)
     * @param bool $uPortableHashes     portable hashes
     *
     * @return Hash
     */
    public function __construct($uIterationCountLog2 = 8, $uPortableHashes = true)
    {
        $this->iterationCountLog2 = $uIterationCountLog2;
        $this->portableHashes = $uPortableHashes;

        $this->randomState = microtime();
        if (function_exists("getmypid")) {
            $this->randomState .= getmypid();
        }
    }

    /**
     * Returns randomly generated binary bytes
     *
     * @param int $uCount number of bytes will be returned
     *
     * @return string random bytes
     */
    public function getRandomBytes($uCount)
    {
        $tOutput = "";

        if (is_readable("/dev/urandom")) {
            $tFileHandle = fopen("/dev/urandom", "rb");
            $tOutput = fread($tFileHandle, $uCount);
            fclose($tFileHandle);
        }

        if (strlen($tOutput) >= $uCount) {
            return $tOutput;
        }

        $tOutput = "";

        for ($tIndex = 0; $tIndex < $uCount; $tIndex += 16) {
            $this->randomState = md5(microtime() . $this->randomState);
            $tOutput .= pack("H*", md5($this->randomState));
        }

        return substr($tOutput, 0, $uCount);
    }

    /**
     * Encodes input to custom alphabet
     *
     * @param string $uInput text input
     * @param int    $uCount length
     *
     * @return string encoded output
     */
    public function encode64($uInput, $uCount)
    {
        $tOutput = "";
        $tIndex = 0;

        do {
            $value = ord($uInput[$tIndex++]);

            $tOutput .= $this->itoa64[$value & 0x3f];
            if ($tIndex < $uCount) {
                $value |= ord($uInput[$tIndex]) << 8;
            }

            $tOutput .= $this->itoa64[($value >> 6) & 0x3f];
            if ($tIndex++ >= $uCount) {
                break;
            }
            if ($tIndex < $uCount) {
                $value |= ord($uInput[$tIndex]) << 16;
            }

            $tOutput .= $this->itoa64[($value >> 12) & 0x3f];
            if ($tIndex++ >= $uCount) {
                break;
            }

            $tOutput .= $this->itoa64[($value >> 18) & 0x3f];
        } while ($tIndex < $uCount);

        return $tOutput;
    }

    /**
     * Generates Private Salt
     *
     * @param string $uInput text input
     *
     * @return string output
     */
    public function gensaltPrivate($uInput)
    {
        return "\$P\$" .
            $this->itoa64[min($this->iterationCountLog2 + 5, 30)] .
            $this->encode64($uInput, 6);
    }

    /**
     * Encrypts Private
     *
     * @param string $uPassword password
     * @param string $uSetting  setting
     *
     * @return string output
     */
    public function cryptPrivate($uPassword, $uSetting)
    {
        $tOutput = "*0";
        if (substr($uSetting, 0, 2) === $tOutput) {
            $tOutput = "*1";
        }

        $tId = substr($uSetting, 0, 3);
        // We use "$P$", phpBB3 uses "$H$" for the same thing
        if ($tId !== "\$P\$" && $tId !== "\$H\$") {
            return $tOutput;
        }

        $tCountLog2 = strpos($this->itoa64, $uSetting[3]);
        if ($tCountLog2 < 7 || $tCountLog2 > 30) {
            return $tOutput;
        }

        $tCount = 1 << $tCountLog2;

        $tSalt = substr($uSetting, 4, 8);
        if (strlen($tSalt) !== 8) {
            return $tOutput;
        }

        // We're kind of forced to use MD5 here since it's the only
        // cryptographic primitive available in all versions of PHP
        // currently in use.  To implement our own low-level crypto
        // in PHP would result in much worse performance and
        // consequently in lower iteration counts and hashes that are
        // quicker to crack (by non-PHP code).
        $tHash = md5($tSalt . $uPassword, true);
        do {
            $tHash = md5($tHash . $uPassword, true);
        } while (--$tCount);

        return substr($uSetting, 0, 12) . $this->encode64($tHash, 16);
    }

    /**
     * Generates Salt Extended
     *
     * @param string $uInput text input
     *
     * @return string output
     */
    public function gensaltExtended($uInput)
    {
        $tCountLog2 = min($this->iterationCountLog2 + 8, 24);
        // This should be odd to not reveal weak DES keys, and the
        // maximum valid value is (2**24 - 1) which is odd anyway.
        $tCount = (1 << $tCountLog2) - 1;

        return "_" .
            $this->itoa64[$tCount & 0x3f] .
            $this->itoa64[($tCount >> 6) & 0x3f] .
            $this->itoa64[($tCount >> 12) & 0x3f] .
            $this->itoa64[($tCount >> 18) & 0x3f] .
            $this->encode64($uInput, 3);
    }

    /**
     * Generates Salt Blowfish
     *
     * @param string $uInput input text
     *
     * @return string output
     */
    public function gensaltBlowfish($uInput)
    {
        // This one needs to use a different order of characters and a
        // different encoding scheme from the one in encode64() above.
        // We care because the last character in our encoded string will
        // only represent 2 bits.  While two known implementations of
        // bcrypt will happily accept and correct a salt string which
        // has the 4 unused bits set to non-zero, we do not want to take
        // chances and we also do not want to waste an additional byte
        // of entropy.
        $tItoa64 = "./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

        $tOutput = "\$2a\$" .
            (chr(ord("0") + $this->iterationCountLog2 / 10)) .
            (chr(ord("0") + $this->iterationCountLog2 % 10)) .
            "\$";

        $tIndex = 0;
        do {
            $tC1 = ord($uInput[$tIndex++]);
            $tOutput .= $tItoa64[$tC1 >> 2];
            $tC1 = ($tC1 & 0x03) << 4;
            if ($tIndex >= 16) {
                $tOutput .= $tItoa64[$tC1];
                break;
            }

            $tC2 = ord($uInput[$tIndex++]);
            $tC1 |= $tC2 >> 4;
            $tOutput .= $tItoa64[$tC1];
            $tC1 = ($tC2 & 0x0f) << 2;

            $tC2 = ord($uInput[$tIndex++]);
            $tC1 |= $tC2 >> 6;
            $tOutput .= $tItoa64[$tC1] . $tItoa64[$tC2 & 0x3f];
        } while (1);

        return $tOutput;
    }

    /**
     * Hashes password
     *
     * @param string $uPassword password
     *
     * @return string hashed password
     */
    public function hashPassword($uPassword)
    {
        $tRandom = "";

        if (!$this->portableHashes) {
            if (CRYPT_BLOWFISH === 1) {
                $tRandom = $this->getRandomBytes(16);
                $tHash = crypt($uPassword, $this->gensaltBlowfish($tRandom));

                if (strlen($tHash) === 60) {
                    return $tHash;
                }
            }

            if (CRYPT_EXT_DES === 1) {
                if (strlen($tRandom) < 3) {
                    $tRandom = $this->getRandomBytes(3);
                }

                $tHash = crypt($uPassword, $this->gensaltExtended($tRandom));
                if (strlen($tHash) === 20) {
                    return $tHash;
                }
            }
        }

        if (strlen($tRandom) < 6) {
            $tRandom = $this->getRandomBytes(6);
        }

        $tHash = $this->cryptPrivate($uPassword, $this->gensaltPrivate($tRandom));
        if (strlen($tHash) === 34) {
            return $tHash;
        }

        // Returning '*' on error is safe here, but would _not_ be safe
        // in a crypt(3)-like function used _both_ for generating new
        // hashes and for validating passwords against existing hashes.
        return "*";
    }

    /**
     * Checks the password matches hashed one or not
     *
     * @param string $uPassword   password
     * @param string $uStoredHash stored hash
     *
     * @return bool true if password matches
     */
    public function checkPassword($uPassword, $uStoredHash)
    {
        $tHash = $this->cryptPrivate($uPassword, $uStoredHash);

        if ($tHash[0] === "*") {
            $tHash = crypt($uPassword, $uStoredHash);
        }

        return $tHash == $uStoredHash;
    }
}
