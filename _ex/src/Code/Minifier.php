<?php
/**
 * Scabbia2 PHP Framework Code
 * https://github.com/eserozvataf/scabbia2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/eserozvataf/scabbia2-fw for the canonical source repository
 * @copyright   2010-2016 Eser Ozvataf. (http://eser.ozvataf.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Code;

use Scabbia\Code\TokenStream;
use Scabbia\Helpers\FileSystem;
use Exception;

/**
 * Minifier
 *
 * @package     Scabbia\Code
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 */
class Minifier
{
    /**
     * Returns a minified php source
     *
     * @param TokenStream $uTokenStream  extracted tokens wrapped with tokenstream
     *
     * @return string the minified file content
     */
    public function minifyPhpSource(TokenStream $uTokenStream)
    {
        $tReturn = "";
        $tLastToken = -1;
        $tOpenStack = [];

        foreach ($uTokenStream as $tToken) {
            // $tReturn .= PHP_EOL . token_name($tToken[0]) . PHP_EOL;
            if ($tToken[0] === T_OPEN_TAG) {
                $tReturn .= "<" . "?php ";
                $tOpenStack[] = $tToken[0];
            } elseif ($tToken[0] === T_OPEN_TAG_WITH_ECHO) {
                $tReturn .= "<" . "?php echo ";
                $tOpenStack[] = $tToken[0];
            } elseif ($tToken[0] === T_CLOSE_TAG) {
                $tLastOpen = array_pop($tOpenStack);

                if ($tLastOpen === T_OPEN_TAG_WITH_ECHO) {
                    $tReturn .= "; ";
                } else {
                    if ($tLastToken !== T_WHITESPACE) {
                        $tReturn .= " ";
                    }
                }

                $tReturn .= "?" . ">";
            } elseif ($tToken[0] === T_COMMENT || $tToken[0] === T_DOC_COMMENT) {
                // skip comments
            } elseif ($tToken[0] === T_WHITESPACE) {
                if ($tLastToken !== T_WHITESPACE &&
                    $tLastToken !== T_OPEN_TAG &&
                    $tLastToken !== T_OPEN_TAG_WITH_ECHO &&
                    $tLastToken !== T_COMMENT &&
                    $tLastToken !== T_DOC_COMMENT
                ) {
                    $tReturn .= " ";
                }
            } elseif ($tToken[0] === null) {
                $tReturn .= $tToken[1];
                if ($tLastToken === T_END_HEREDOC) {
                    $tReturn .= "\n";
                    $tToken[0] = T_WHITESPACE;
                }
            } else {
                $tReturn .= $tToken[1];
            }

            $tLastToken = $tToken[0];
        }

        while (count($tOpenStack) > 0) {
            $tLastOpen = array_pop($tOpenStack);
            if ($tLastOpen === T_OPEN_TAG_WITH_ECHO) {
                $tReturn .= "; ";
            } else {
                if ($tLastToken !== T_WHITESPACE) {
                    $tReturn .= " ";
                }
            }

            $tReturn .= "?" . ">";
        }

        return $tReturn;
    }
}
