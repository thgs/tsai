<?php

function syntaxView()
{
    $args = func_get_args();
    
    $ret = '';
    
    foreach ($args as $for)
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
            
        $ret .= PHP_EOL."\t".$for.' :: '.$parameters;
    }
    
    return substr($ret, strlen(PHP_EOL));
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


    /*
    -------------------------------------------------------------------------
    | InteractLoopWithPrompt
    -------------------------------------------------------------------------
    |
    | This class should create an Interact Loop with a prompt ! :D
    | Also it implements __invoke() which maps to run().
    |
    */
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
            
            $func = $this->f;
            
            $this->output( $func( $this->input() ) );
            
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


    /*
    -------------------------------------------------------------------------
    | Command Container
    -------------------------------------------------------------------------
    |
    | This class is a container for commands. Uses an inner array container
    | and every key of the container is the command's call string.
    |
    */
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


    /*
    -------------------------------------------------------------------------
    | Command Dispatcher
    -------------------------------------------------------------------------
    |
    | CommandDispatcher is supposed to use a container and a parser to 
    | lookup for the appropriate command and return it (dispatchFromInput).
    | Also, this class implements __invoke(input), which will dispatch a command
    | and additionally execute it and return its result.
    |
    |
    */
class CommandDispatcher
{

    protected $container;
    
    
    public function __construct($container, $parser)
    {
        $this->container = $container;
        $this->parser = $parser;
    }
    
    
    public function dispatchFromInput($input)
    {
        if ($this->parser->isCommand($input))
        {
            list($cmd, $args) = $this->parser->parseInput($input);
            
            return [$this->container->lookup($cmd), $args];
        }
        else
        {
            # parseInput bug here
            list($_, $args) = $this->parser->parseInput('_'.$input);
            
            $cmd = $this->container->lookup('_');
            
            if (substr($cmd, 0, 1) == '@')
            {
                $key = substr($cmd, 1);
                
                $newArgs = array_merge([$_], $args);
                
                return [$this->container->lookup($key), $newArgs];
            }
            
            // else it should be callable! :D
        }
        
        return null;
    }
    
    public function __invoke($input)
    {
        list($command, $args) = $this->dispatchFromInput($input);
        
        return call_user_func_array($command, $args);
    }
}


    /*
    -------------------------------------------------------------------------
    | CommandParser
    -------------------------------------------------------------------------
    |
    | Class to parse a string into command and its arguments
    |
    | Note: see CommandDispatcher/ # parseInput bug here that needs correction
    |
    */
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
        return array_map('trim', explode(' ', trim($argString)));
    }
    
}

    /*
    -------------------------------------------------------------------------
    | Application Run
    -------------------------------------------------------------------------
    |
    | Here, we initialize all our objects and start the loop.
    |
    */

$parser = new CommandParser;
$container = new CommandContainer([
    't' =>  'syntaxview',
    'q' =>  function($x = 0) { echo 'Bye!'; exit($x); },
    'i' =>  new IncludeCommand,
    '_' =>  '@t'
]);
$dispatcher = new CommandDispatcher($container, $parser);
$ioLoop = new InteractLoopWithPrompt($dispatcher);
$ioLoop();



class IncludeCommand
{
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
    
    public function __invoke()
    {
        $arguments = func_get_args();
        
        return $this->command($arguments);
    }
}
