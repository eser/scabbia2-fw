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
use Scabbia\Framework\Io;
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
    /** @type array configuration content */
    public $content = [];
    /** @type null|array node stack */
    protected $nodeStack = null;


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
     * @param string $uPath      path of configuration file
     * @param bool   $uOverwrite overwrite existing values
     *
     * @return void
     */
    public function add($uPath, $uOverwrite = false)
    {
        $tConfigContent = Io::readFromCache(
            $uPath,
            function () use ($uPath) {
                $tParser = new Parser();
                return $tParser->parse(Io::read($uPath));
            },
            [
                "ttl" => 60 * 60
            ]
        );

        $this->process($this->content, $tConfigContent, $uOverwrite);
    }

    /**
     * Processes the configuration file in order to simplify its accessibility
     *
     * @param mixed $uTarget     target reference
     * @param mixed $uNode       source object
     * @param bool  $uOverwrite  overwrite existing values
     *
     * @return void
     */
    protected function process(&$uTarget, $uNode, $uOverwrite)
    {
        // TODO: array concat
        if (is_scalar($uNode)) {
            if ($this->nodeStack !== null) {
                $uTarget[implode("/", $this->nodeStack)] = $uNode;
                return;
            }

            $uTarget = $uNode;
            return;
        }

        $tOverwrite = $uOverwrite;
        if ($uNode === null) {
            return;
        }

        foreach ($uNode as $tKey => $tSubnode) {
            $tNodeParts = explode("|", $tKey);

            $tNodeKey = array_shift($tNodeParts);
            foreach ($tNodeParts as $tNodePart) {
                if ($tNodePart === "disabled") {
                    continue 2;
                } elseif ($tNodePart === "development") {
                    if (ApplicationBase::$current === null || !ApplicationBase::$current->development) {
                        continue 2;
                    }
                } elseif ($tNodePart === "important") {
                    $tOverwrite = true;
                } elseif ($tNodePart === "flat") {
                    $this->nodeStack = [];
                }
            }

            if ($this->nodeStack !== null) {
                $this->nodeStack[] = $tNodeKey;
                $this->process($uTarget, $tSubnode, $tOverwrite);

                if (array_pop($this->nodeStack) === false) {
                    $this->nodeStack = null;
                }
                continue;
            }

            if (!isset($uTarget[$tNodeKey]) || $tOverwrite) {
                $this->process($uTarget[$tNodeKey], $tSubnode, $tOverwrite);
            }
        }
    }
}
