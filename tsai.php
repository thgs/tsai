<?php

function syntaxView($for)
{
    $refl = new ReflectionFunction($for);
    
    $parameters = implode(' -> ', 
    array_map(
    function ($param) {
        $name = ($param->isOptional())
            ? '['.$param->getName().']'
            : $param->getName();
            
        return $name;
                
     }, $refl->getParameters()
     ));
        
    return $for.' :: '.$parameters;
}


# 0/null for all, 1 for user only, 2 for internal only
function listFunctions($show = null)
{
    $funcs = get_declared_functions();
    
    switch ($show)
    {
        case 1:     return $funcs['user'];
        case 2:     return $funcs['internal'];
        case 0:
        case null:  return array_values($funcs);
        /* there is something wrong with this code ?
        default:
            $ret = ((is_string($show)) && (isset($funcs[$show]))
                ? $funcs[$show] : array();
            return $ret;
        */
    }
}

function listClasses()
{
    return get_declared_classes();
}


function listIncludes()
{
    return get_included_files();
}

# more functions    ------------
# methodSyntaxView
# listFunctions             done
# listClasses               done
# listIncludes              done
# findCodeForFunction
# findCodeForClass
# classInsSyntaxView
# returnValView



class InteractLoopWithPrompt
{    
    public $prompt = 'tsai> ';
    public $newline = PHP_EOL;
    
    protected $f, $inputHandle, $outputHandle;
    
    public function __construct($decisionFunction, $inputHandle = null, $outputHandle = null)
    {
        $this->f = $decisionFunction;
        
        $this->inputHandle  = ($inputHandle)  ?: fopen('php://stdin', 'r');
        $this->outputHandle = ($outputHandle) ?: fopen('php://stdout', 'w');
    }
    
    public function run()
    {
        while (true)
        {
            $this->output($this->prompt);
            
            $this->output( $this->f( $this->input() ) );
            
            $this->output($this->newline);
        }
    }
    
    public function output($string)
    {
        return fwrite($this->outputHandle, $string);
    }
    
    public function input()
    {
        return fgets($this->inputHandle);
    }
    
    public function __invoke()
    {
        return $this->run();
    }
    
    public function __sleep()
    {
        fclose($this->inputHandle);
        fclose($this->outputHandle);
    }
    
}


class CommandContainer
{
    protected $commands;
    
    public function __construct($commands = [])
    {
        $this->commands = $commands;
    }
    
    
    public function lookup($cmd)
    {
        return (isset($this->commands[$cmd])) ? $this->commands[$cmd] : false;
    }
    
    
    public function add($key, $command)
    {
        if (! isset($this->commands[$key])) 
        {
            $this->commands[$key] = $command;
            
            return true;
        }
        
        return false;
    }
    
    
    public function getCommands()
    {
        return $this->commands;
    }
    
}

class CommandDispatcher
{

    protected $container;
    
    
    public function __construct($container, $parser)
    {
        $this->container = $container;
    }
    
    
    public function dispatchFromInput($input)
    {
        if ($this->parser->isCommand($input))
        {
            list($cmd, $args) = $this->parser->parseInput($input);
            
            return $this->container->lookup($cmd);
        }
        
        return null;
    }
    
    /*
    
    public function process($input)
    {
        if (!is_null($c = $this->dispatchFromInput($input)))
        {
            $c->
        }
    }
    */
    
}



class CommandParser
{
    protected $commandInit = ':';


    public function isCommand($input)
    {
        return (substr($input, 0, strlen($this->commandInit)) == $this->commandInit);
    }


    public function parseInput($input)
    {
        $preparedInput = substr(trim($input), 1);
        
        list($cmd, $argString) = explode(' ', $preparedInput, 2);
        
        return [$cmd, $this->parseArguments($argString)];
    }
    
    
    public function parseArguments($argString)
    {
        return explode(' ', array_map('trim', $argString));
    }
    
}


class CommandExecuter
{
    public function execute($command, $arguments = [])
    {
        
    }
}


    /*
    -------------------------------------------------------------------------
    | Repol -  A primitive input-output loop
    -------------------------------------------------------------------------
    |
    |
    |
    */
class Repol
{
    public $prompt = '> ';
    public $newline = PHP_EOL;
    
    protected $input;
    protected $output;

    protected $commands;
    
    public function __construct()
    {
        # set commands
        $this->commands = [
            new ExitCommand,
            new DefViaReflCommand,
            new IncludeCommand
        ];
    }
    
    public function run($input = null, $output = null)
    {
        # set input
        $this->input = ($input) ?: fopen('php://stdin', 'r');
        
        # set output
        $this->output = ($output) ?: fopen('php://stdout', 'w');
        
        # start loop
        while(true)
        {
            # output prompt
            $this->output($this->prompt);
            
            # get and process input
            $in = $this->input();
            $out = $this->process($in);
            $this->output($out);
            
            # output a newline
            $this->output($this->newline);
        }
    }
    
    public function input()
    {
        return fread($this->input, 255);
    }
    
    public function output($string)
    {
        fwrite($this->output, $string);
    }
    
    public function add($command)
    {
        $this->commands[] = $command;
    }
    
    public function process($input)
    {
        $isCommand = (substr($input, 0, 1) == ':');
        if ($isCommand)
        {
            return $this->processCommand($input);
        }
        else
        {
            return $this->processCommand(':t '.$input);
        }
    }
    
    public function processCommand($input)
    {
        $cmd = substr($input, 1, 1);
        
        foreach ($this->commands as $c)
        {
            if ($c->getCmd() == $cmd)
            {
                return $c->command($this->parseArguments($input));
            }
        }
        
    }
    
    public function parseArguments($input)
    {
        $e = explode(' ', trim($input));
        
        unset($e[0]);
        
        return array_values(array_map('trim', $e));        
    }
}

    /*
    -------------------------------------------------------------------------
    | abstract Repol Command class
    -------------------------------------------------------------------------
    |
    | 
    |
    */
abstract class RepolCommand
{
    protected $cmd, $arguments;
        
    abstract public function command($arguments);
    
    public function getCmd()
    {
        return $this->cmd;
    }
    
    #abstract public function getHelpLine();
    #abstract public function getHelp();
}


    /*
    -------------------------------------------------------------------------
    | Default Repol Commands
    -------------------------------------------------------------------------
    |
    | ExitCommand
    | DefViaReflCommand
    | 
    |
    |
    */

class ExitCommand extends RepolCommand
{
    protected $cmd = 'q';
    
    public function command($arguments)
    {
        $exitCode = (isset($arguments[0])) ? $arguments[0] : 0;
        echo 'Bye..'.PHP_EOL;
        exit($exitCode);
    }
}

class DefViaReflCommand extends RepolCommand
{
    protected $cmd = 't';

    public function command($arguments)
    {
        return syntaxView($arguments[0]);
    }
}

class IncludeCommand extends RepolCommand
{
    protected $cmd = 'i';
    
    public function command($arguments)
    {
        # include all files from arguments
        foreach ($arguments as $arg)
        {
            if (is_file($arg))
            {
                include_once $arg;
            }
        }
        
        # return that OKay..
        return 'Okay, included '.count($arguments).' file(s).';
    }
}


    /*
    -------------------------------------------------------------------------
    | Application Run
    -------------------------------------------------------------------------
    |
    | Here, we initialize the Repol and start the loop.
    |
    */
    
$repol = new Repol;
$repol->run();
