## tsai
A small "shell" to print out the syntax of a function in PHP using Reflection

At the moment, I just made a repository with the very-first implementation of it.


##### Usage

Basic usage demonstrated below

```
$ php tsai.php
tsai> strlen abs explode array_push
        strlen :: str
        abs :: number
        explode :: separator -> str -> [limit]
        array_push :: stack -> var -> [...]
tsai> :i tsai.php
Okay, included 1 file(s).
tsai> :li
[/home/ubuntu/workspace/tsai.php]
tsai> :lcm CommandDispatcher
[
        CommandDispatcher::__construct :: container -> parser, 
        CommandDispatcher::dispatchFromInput :: input, 
        CommandDispatcher::__invoke :: input
]
tsai> :lf 1
[ats]
tsai> :q
Bye..
```

-- Still work in progress -- 
