<?php

class Console
{
    const PARAMETER = 0;
    const PARAMETER_REQUIRED = 1;
    const OPTION = 2;
    const OPTION_MULTIPLE = 3;
    const OPTION_FLAG = 4;

    protected $title;
    protected $description;
    protected $commands = [];
    
    public function __construct($uTitle, $uDescription)
    {
        $this->title = $uTitle;
        $this->description = $uDescription;
    }

    public function addCommand($uCommandName, $uCommandDescription, array $uParameters)
    {
        $this->commands[$uCommandName] = [$uCommandDescription, $uParameters];
    }
    
    public function help()
    {
        echo "{$this->title}\n";
        echo "{$this->description}\n";

        foreach ($this->commands as $tCommandKey => $tCommand) {
            echo "- {$tCommandKey}: {$tCommand[0]}\n";
        }
    }

    public function parse($uCommandLine) {
        $tParts = $this->split($uCommandLine);
        $tPartCommand = array_shift($tParts[0]);

        if (/* $tPartCommand !== false && */ !isset($this->commands[$tPartCommand])) {
            return null;
        }

        return $this->executeCommand($tPartCommand, $tParts[0], $tParts[1]);
    }
    
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

    protected function executeCommand($uCommandKey, array $uCommandParameters = [], array $uCommandOptions = [])
    {
        $tParameters = [];
        
        if (!isset($this->commands[$uCommandKey])) {
            throw new Exception("command not found - {$uCommandKey}");
        }

        foreach ($this->commands[$uCommandKey][1] as $tOption) {
            if ($tOption[0] === Console::PARAMETER) {
                $tParameters[$tOption[1]] = array_shift($uCommandParameters);
            } elseif ($tOption[0] === Console::PARAMETER_REQUIRED) {
                $tParameters[$tOption[1]] = array_shift($uCommandParameters);
                if ($tParameters[$tOption[1]] === false) {
                    throw new Exception("{$tOption[1]} parameter required for command {$uCommandKey}");
                }
            } elseif ($tOption[0] === Console::OPTION || $tOption[0] === Console::OPTION_MULTIPLE || $tOption[0] === Console::OPTION_FLAG) {
                $tParameters[$tOption[1]] = $this->extractParameter($uCommandOptions, $tOption);
            }
        }

        if (count($uCommandOptions) > 0) {
            throw new Exception("Invalid options used - " . implode($uCommandOptions, ", "));
        }

        return $tParameters;
    }
    
    protected function extractParameter(&$uCommandParameters, $uOption, $uAssignment = false) {
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

$x = new Console("EserConsole", "");
$x->addCommand(
    "test",
    "Just for testing purposes...",
    [
        // type, name, description
        [Console::PARAMETER_REQUIRED, "who", ""],
        [Console::PARAMETER, "whom", ""],
        [Console::OPTION, "--flag", ""],
        [Console::OPTION_MULTIPLE, "--flags", ""],
        [Console::OPTION_FLAG, "--silent", ""]
    ]
);

echo "<pre>";
var_dump($x->parse("test \"eser ozvataf\" seyma --flag=yes --flags=a --flags=b --silent"));
