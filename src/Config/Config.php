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

namespace Scabbia\Config;

use Scabbia\Framework\ApplicationBase;
use Scabbia\Framework\Core;
use Scabbia\Helpers\FileSystem;
use Scabbia\Yaml\Dumper;
use Scabbia\Yaml\Parser;

/**
 * Config
 *
 * @package     Scabbia\Config
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.0.0
 */
class Config
{
    /** @type int NONE      no flag */
    const NONE = 0;
    /** @type int OVERWRITE overwrite existing nodes by default */
    const OVERWRITE = 1;
    /** @type int FLATTEN   flatten nodes by default */
    const FLATTEN = 2;


    /** @type array configuration paths */
    public $paths = [];
    /** @type array configuration content */
    public $content = [];


    /**
     * Loads a configuration file
     *
     * @param string $uPath path of configuration file to be loaded
     *
     * @return mixed
     */
    public static function load($uPath)
    {
        $tInstance = new static();
        $tInstance->add($uPath);

        return $tInstance;
    }

    /**
     * Adds a file into configuration compilation
     *
     * @param string $uPath   path of configuration file
     * @param int    $uFlags  loading flags
     *
     * @return void
     */
    public function add($uPath, $uFlags = self::NONE)
    {
        $this->paths[] = [$uPath, $uFlags];
    }

    /**
     * Compiles given configuration files into single configuration
     *
     * @return array final configuration
     */
    public function get()
    {
        // TODO mass caching with pathnames and flags
        foreach ($this->paths as $tPath) {
            $tConfigPath = Core::translateVariables($tPath[0]);
            $tConfigContent = Core::cachedRead(
                $tConfigPath,
                function () use ($tConfigPath) {
                    $tParser = new Parser();
                    return $tParser->parse(FileSystem::read($tConfigPath));
                },
                [
                    "ttl" => 60 * 60
                ]
            );

            $this->process($this->content, $tConfigContent, $tPath[1]);
        }

        return $this->content;
    }

    /**
     * Gets the YAML representation of configuration stack
     *
     * @return string YAML
     */
    public function dump()
    {
        return Dumper::dump($this->get(), 1);
    }

    /**
     * Processes the configuration file in order to simplify its accessibility
     *
     * @param mixed $uTarget  target reference
     * @param mixed $uNode    source object
     * @param int   $uFlags   loading flags
     *
     * @return void
     */
    public function process(&$uTarget, $uNode, $uFlags)
    {
        $tQueue = [
            [[], $uNode, $uFlags, &$uTarget, null, false]
        ];

        do {
            $tItem = array_pop($tQueue);

            if ($tItem[4] === null) {
                $tRef = &$tItem[3];
            } else {
                $tRef = &$tItem[3][$tItem[4]];
            }

            if (is_scalar($tItem[1]) || $tItem[1] === null) {
                if (!isset($tRef) || ($tItem[2] & self::OVERWRITE) === self::OVERWRITE) {
                    $tRef = $tItem[1];
                }

                continue;
            }

            if (!is_array($tRef) || ($tItem[2] & self::OVERWRITE) === self::OVERWRITE) {
                $tRef = []; // initialize as an empty array
            }

            foreach ($tItem[1] as $tKey => $tSubnode) {
                $tFlags = $tItem[2];
                $tListNode = false;

                $tNodeParts = explode("|", $tKey);
                $tNodeKey = array_shift($tNodeParts);

                if ($tItem[5] && is_numeric($tNodeKey)) {
                    $tNodeKey = count($tRef);
                }

                foreach ($tNodeParts as $tNodePart) {
                    if ($tNodePart === "disabled") {
                        continue 2;
                    } elseif ($tNodePart === "development") {
                        if (ApplicationBase::$current === null || !ApplicationBase::$current->development) {
                            continue 2;
                        }
                    } elseif ($tNodePart === "list") {
                        $tListNode = true;
                    } elseif ($tNodePart === "important") {
                        $tFlags |= self::OVERWRITE;
                    } elseif ($tNodePart === "flat") {
                        $tFlags |= self::FLATTEN;
                    }
                }

                $tNewNodeKey = $tItem[0];
                if (($tFlags & self::FLATTEN) === self::FLATTEN) {
                    $tNodeKey = ltrim("{$tItem[4]}/{$tNodeKey}", "/");
                    $tNewNodeKey[] = $tNodeKey;

                    $tQueue[] = [$tNewNodeKey, $tSubnode, $tFlags, &$tRef, $tNodeKey, $tListNode];
                } else {
                    $tNewNodeKey[] = $tNodeKey;
                    $tQueue[] = [$tNewNodeKey, $tSubnode, $tFlags, &$tRef[$tNodeKey], null, $tListNode];
                }
            }
        } while (count($tQueue) > 0);
    }
}
