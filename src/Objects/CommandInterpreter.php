<?php
/**
 * Scabbia2 PHP Framework Code
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        http://github.com/scabbiafw/scabbia2-fw for the canonical source repository
 * @copyright   2010-2014 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Objects;

use Scabbia\Interfaces\IInterface;

/**
 * Command Interpreter
 *
 * @package     Scabbia\Objects
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class CommandInterpreter
{
    const PARAMETER = 0;
    const PARAMETER_REQUIRED = 1;
    const OPTION = 2;
    const OPTION_MULTIPLE = 3;
    const OPTION_FLAG = 4;

    protected $title;
    protected $description;
    protected $commands = [];

    /**
     * Initializes an interpreter
     *
     * @param string $uTitle       title of interpreter
     * @param string $uDescription description for the interpreter
     *
     * @return CommandInterpreter
     */
    public function __construct($uTitle, $uDescription)
    {
        $this->title = $uTitle;
        $this->description = $uDescription;
    }

    /**
     * Adds a new command to interpreter
     *
     * @param string $uCommandName        name of the command
     * @param string $uCommandDescription command description
     * @param array  $uParameters         parameters
     *
     * @return void
     */
    public function addCommand($uCommandName, $uCommandDescription, array $uParameters)
    {
        $this->commands[$uCommandName] = [$uCommandDescription, $uParameters];
    }

    /**
     * Displays the help
     *
     * @param IInterface $uInterface interface class
     *
     * @return void
     */
    public function help($uInterface)
    {
        $uInterface->write(sprintf("%s\n", $this->title));
        $uInterface->write(sprintf("%s\n", $this->description));

        foreach ($this->commands as $tCommandKey => $tCommand) {
            $uInterface->write(sprintf("- %s: %s\n", $tCommandKey, $tCommand[0]));
        }
    }

    /**
     * Parses and executes a command
     *
     * @param string $uCommandLine command and arguments
     *
     * @return array|null result of command execution
     */
    public function parse($uCommandLine)
    {
        $tParts = $this->split($uCommandLine);
        $tPartCommand = array_shift($tParts[0]);

        if (/* $tPartCommand !== false && */ !isset($this->commands[$tPartCommand])) {
            return null;
        }

        return $this->executeCommand($tPartCommand, $tParts[0], $tParts[1]);
    }

    /**
     * Splits a command into its components
     *
     * @param string $uInput the command
     *
     * @return array components of the given command
     */
    protected function split($uInput)
    {
        $tParts = [[], []];
        $tBuffer = "";
        $tQuote = false;

        for ($tPosition = 0, $tLen = strlen($uInput); $tPosition < $tLen; $tPosition++) {
            if ($uInput[$tPosition] === "\"") {
                $tQuote = !$tQuote;
                continue;
            } elseif (ctype_space($uInput[$tPosition])) {
                if (strlen($tBuffer) == 0) {
                    continue;
                }

                if (!$tQuote) {
                    if (strncmp($tBuffer, "--", 2) === 0) {
                        $tParts[1][] = $tBuffer;
                    } else {
                        $tParts[0][] = $tBuffer;
                    }

                    $tBuffer = "";
                    continue;
                }
            }

            $tBuffer .= $uInput[$tPosition];
        }

        if (strlen($tBuffer) > 0) {
            if (strncmp($tBuffer, "--", 2) === 0) {
                $tParts[1][] = $tBuffer;
            } else {
                $tParts[0][] = $tBuffer;
            }
        }

        return $tParts;
    }

    /**
     * Executes a command
     *
     * @param string $uCommandKey        name of the command
     * @param array  $uCommandParameters parameters
     * @param array  $uCommandOptions    options
     *
     * @throws Exception
     * @return array
     */
    protected function executeCommand($uCommandKey, array $uCommandParameters = [], array $uCommandOptions = [])
    {
        $tParameters = [];

        if (!isset($this->commands[$uCommandKey])) {
            throw new Exception(sprintf("command not found - %s", $uCommandKey));
        }

        foreach ($this->commands[$uCommandKey][1] as $tOption) {
            if ($tOption[0] === Console::PARAMETER) {
                $tParameters[$tOption[1]] = array_shift($uCommandParameters);
            } elseif ($tOption[0] === Console::PARAMETER_REQUIRED) {
                $tParameters[$tOption[1]] = array_shift($uCommandParameters);
                if ($tParameters[$tOption[1]] === false) {
                    throw new Exception(sprintf("%s parameter required for command %s", $tOption[1], $uCommandKey));
                }
            } elseif ($tOption[0] === Console::OPTION ||
                $tOption[0] === Console::OPTION_MULTIPLE ||
                $tOption[0] === Console::OPTION_FLAG) {
                $tParameters[$tOption[1]] = $this->extractParameter($uCommandOptions, $tOption);
            }
        }

        if (count($uCommandOptions) > 0) {
            throw new Exception(sprintf("Invalid options used - %s", implode($uCommandOptions, ", ")));
        }

        return $tParameters;
    }

    /**
     * Extracts a parameter
     *
     * @param array  $uCommandParameters set of command parameters
     * @param string $uOption            name of the option
     * @param bool   $uAssignment        whether is it an assignment or not
     *
     * @return mixed extracted parameter
     */
    protected function extractParameter(&$uCommandParameters, $uOption, $uAssignment = false)
    {
        if ($uOption[0] === Console::OPTION) {
            $tReturn = null;
            $tOption = "{$uOption[1]}=";
        } elseif ($uOption[0] === Console::OPTION_MULTIPLE) {
            $tReturn = [];
            $tOption = "{$uOption[1]}=";
        } elseif ($uOption[0] === Console::OPTION_FLAG) {
            $tReturn = false;
            $tOption = $uOption[1];
        }
        $tOptionLength = strlen($tOption);

        $tUnsetKeys = [];

        foreach ($uCommandParameters as $tCommandParameterKey => $tCommandParameter) {
            if ($uOption[0] === Console::OPTION) {
                if (strncmp($tCommandParameter, $tOption, $tOptionLength) === 0) {
                    $tUnsetKeys[] = $tCommandParameterKey;
                    $tReturn = substr($tCommandParameter, $tOptionLength);
                }
            } elseif ($uOption[0] === Console::OPTION_MULTIPLE) {
                if (strncmp($tCommandParameter, $tOption, $tOptionLength) === 0) {
                    $tUnsetKeys[] = $tCommandParameterKey;
                    $tReturn[] = substr($tCommandParameter, $tOptionLength);
                }
            } elseif ($uOption[0] === Console::OPTION_FLAG) {
                if (strcmp($tCommandParameter, $tOption) === 0) {
                    $tUnsetKeys[] = $tCommandParameterKey;
                    $tReturn = true;
                }
            }
        }

        foreach ($tUnsetKeys as $tUnsetKey) {
            unset($uCommandParameters[$tUnsetKey]);
        }

        return $tReturn;
    }
}
