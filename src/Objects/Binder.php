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

namespace Scabbia\Objects;

use Scabbia\Framework\Core;
use Scabbia\Helpers\Io;

/**
 * Binder
 *
 * @package     Scabbia\Objects
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.5.0
 */
class Binder
{
    /** @type string name */
    public $name = null;

    /** @type string cache path */
    public $cachepath;

    /** @type bool sealed */
    public $sealed;

    /** @type array contents */
    public $contents = [];

    /** @type string output */
    public $output;


    /**
     * Initializes a Binder class instance
     *
     * @param string $uName a name for binder
     *
     * @return Binder
     */
    public function __construct($uName)
    {
        $this->name = $uName;

        $this->cachepath = Core::$basepath . "/writable/cache/" . crc32("binder/{$uName}");
        $tOptions = [
            "ttl" => 60 * 60
        ];

        if ($this->sealed = Io::isReadable($this->cachepath, $tOptions)) {
            $this->output = Io::readSerialize($this->cachepath);
        }
    }

    /**
     * Adds a content
     *
     * @param string $uContent       content
     * @param string $uMimeType      mimetype of content
     *
     * @return void
     */
    public function addContent($uContent, $uMimeType = "text/plain")
    {
        if ($this->sealed) {
            return;
        }

        $this->contents[] = ["direct", $uMimeType, $uContent];
    }

    /**
     * Adds a callback
     *
     * @param callable $uCallback  callback
     * @param string   $uMimeType  mimetype of content
     *
     * @return void
     */
    public function addCallback($uCallback, $uMimeType = "text/plain")
    {
        if ($this->sealed) {
            return;
        }

        $this->contents[] = ["callback", $uMimeType, $uCallback];
    }

    /**
     * Adds a file
     *
     * @param string $uPath  path of the content
     *
     * @return void
     */
    public function addFile($uPath)
    {
        if ($this->sealed) {
            return;
        }

        $tFilePath = Core::translateVariables($uPath);
        $tExtension = pathinfo($tFilePath, PATHINFO_EXTENSION);
        $this->contents[] = ["file", Io::getMimetype($tExtension), $tFilePath];
    }

    /**
     * Compiles given configuration files into single configuration
     *
     * @param null|string $uMimeTypeFilter only compiles filtered mimetypes
     *
     * @return void
     */
    public function compile($uMimeTypeFilter = null)
    {
        if ($this->sealed) {
            return;
        }

        $this->sealed = true;
        $this->output = "";

        foreach ($this->contents as $tContent) {
            if ($uMimeTypeFilter !== null && $uMimeTypeFilter !== $tContent[1]) {
                continue;
            }

            if ($tContent[0] === "direct") {
                $this->output .= $tContent[2];
            } elseif ($tContent[0] === "callback") {
                $this->output .= call_user_func($tContent[2]);
            } elseif ($tContent[0] === "file") {
                $this->output .= Core::cachedRead(
                    $tContent[2],
                    function () use ($tContent) {
                        return Io::read($tContent[2]);
                    },
                    [
                        "ttl" => 60 * 60
                    ]
                );
            }
        }

        Io::writeSerialize($this->cachepath, $this->output);
    }
}
