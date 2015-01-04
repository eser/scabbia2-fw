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
 *
 * -------------------------
 * Portions of this code are from Symfony YAML Component under the MIT license.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE-MIT
 * file that was distributed with this source code.
 *
 * Modifications made:
 * - Scabbia Framework code styles applied.
 * - All dump methods are moved under Dumper class.
 * - Redundant classes removed.
 * - Namespace changed.
 * - Tests ported to Scabbia2.
 * - Encoding checks removed.
 */

namespace Scabbia\Yaml;

use RuntimeException;

/**
 * Exception class thrown when an error occurs during parsing
 *
 * @package     Scabbia\Yaml
 * @author      Fabien Potencier <fabien@symfony.com>
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class ParseException extends RuntimeException
{
    /** @type null|string   $parsedFile     The file name where the error occurred */
    protected $parsedFile;
    /** @type int           $parsedLine     The line where the error occurred */
    protected $parsedLine;
    /** @type int|null      $snippet        The snippet of code near the problem */
    protected $snippet;
    /** @type string        $rawMessage     The error message */
    protected $rawMessage;


    /**
     * Constructor
     *
     * @param string     $message    The error message
     * @param int        $parsedLine The line where the error occurred
     * @param int        $snippet    The snippet of code near the problem
     * @param string     $parsedFile The file name where the error occurred
     * @param \Exception $previous   The previous exception
     *
     * @return ParseException
     */
    public function __construct(
        $message,
        $parsedLine = -1,
        $snippet = null,
        $parsedFile = null,
        \Exception $previous = null
    ) {
        $this->parsedFile = $parsedFile;
        $this->parsedLine = $parsedLine;
        $this->snippet = $snippet;
        $this->rawMessage = $message;

        $this->updateRepr();

        parent::__construct($this->message, 0, $previous);
    }

    /**
     * Gets the snippet of code near the error
     *
     * @return string The snippet of code
     */
    public function getSnippet()
    {
        return $this->snippet;
    }

    /**
     * Sets the snippet of code near the error
     *
     * @param string $snippet The code snippet
     *
     * @return void
     */
    public function setSnippet($snippet)
    {
        $this->snippet = $snippet;

        $this->updateRepr();
    }

    /**
     * Gets the filename where the error occurred
     *
     * This method returns null if a string is parsed.
     *
     * @return string The filename
     */
    public function getParsedFile()
    {
        return $this->parsedFile;
    }

    /**
     * Sets the filename where the error occurred
     *
     * @param string $parsedFile The filename
     *
     * @return void
     */
    public function setParsedFile($parsedFile)
    {
        $this->parsedFile = $parsedFile;

        $this->updateRepr();
    }

    /**
     * Gets the line where the error occurred
     *
     * @return int The file line
     */
    public function getParsedLine()
    {
        return $this->parsedLine;
    }

    /**
     * Sets the line where the error occurred
     *
     * @param int $parsedLine The file line
     */
    public function setParsedLine($parsedLine)
    {
        $this->parsedLine = $parsedLine;

        $this->updateRepr();
    }

    /**
     * Updates the generated message
     *
     * @return void
     */
    protected function updateRepr()
    {
        $this->message = $this->rawMessage;

        $dot = false;
        if (substr($this->message, -1) === ".") {
            $this->message = substr($this->message, 0, -1);
            $dot = true;
        }

        if ($this->parsedFile !== null) {
            $this->message .= sprintf(
                " in %s",
                json_encode($this->parsedFile, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }

        if ($this->parsedLine >= 0) {
            $this->message .= sprintf(" at line %d", $this->parsedLine);
        }

        if ($this->snippet) {
            $this->message .= sprintf(" (near \"%s\")", $this->snippet);
        }

        if ($dot) {
            $this->message .= ".";
        }
    }
}
