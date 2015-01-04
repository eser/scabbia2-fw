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

namespace Scabbia\Objects;

use Scabbia\Helpers\FileSystem;

/**
 * Binder
 *
 * @package     Scabbia\Objects
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.5.0
 */
class Binder
{
    /** @type array filters */
    public $filters = [];

    /** @type array contents */
    public $contents = [];

    /** @type string output */
    public $output;


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
        $tExtension = pathinfo($uPath, PATHINFO_EXTENSION);
        $this->contents[] = ["file", FileSystem::getMimetype($tExtension), $uPath];
    }

    /**
     * Adds a callback
     *
     * @param string   $uMimeType  mimetype to be registered
     * @param callable $uCallback  callback
     *
     * @return void
     */
    public function addFilter($uMimeType, $uCallback)
    {
        $this->filters[$uMimeType] = $uCallback;
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
        if (count($this->contents) > 0) {
            $this->output = "";

            foreach ($this->contents as $tContent) {
                if ($uMimeTypeFilter !== null && $uMimeTypeFilter !== $tContent[1]) {
                    continue;
                }

                if ($tContent[0] === "direct") {
                    $tOutput = $tContent[2];
                } elseif ($tContent[0] === "callback") {
                    $tOutput = call_user_func($tContent[2]);
                } elseif ($tContent[0] === "file") {
                    $tOutput = FileSystem::read($tContent[2]);
                }

                if (isset($this->filters[$tContent[1]])) {
                    $tOutput = call_user_func($this->filters[$tContent[1]], $tOutput);
                }

                $this->output .= $tOutput;
            }

            $this->contents = [];
        }

        return $this->output;
    }
}
