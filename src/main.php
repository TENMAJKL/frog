<?php

$auto = 37;

function auto($reset=false)
{
    global $auto;
    if (!$reset)
        $auto+= 1;
    else
        $auto = 0;
    return $auto;
}


define("FROG_STRING", auto());
define("FROG_INTEGER", auto());
define("FROG_END", auto());
define("FROG_ECHO", auto());
define("FROG_NL", auto());
define("FROG_VARIABLE", auto());
define("FROG_ASSIGN", auto());

$data_types = [
    FROG_INTEGER => "int",
    FROG_STRING => "char*"
];

$tokens = [
    ["(?:\"([^\"]*?)\")|(?:'([^']*?)')", FROG_STRING],
    ["-?[0-9]+", FROG_INTEGER],
    [";", FROG_END],
    ["echo", FROG_ECHO],
    ["\\n", FROG_NL], // Counting lines
	['\$[a-z]+', FROG_VARIABLE],
	["=", FROG_ASSIGN]
];

function lexer($code)
{
    $result = [];
    global $tokens;

    foreach ($tokens as [$expression, $token])
    {
        if (preg_match_all("/{$expression}/mi", $code, $matches, PREG_OFFSET_CAPTURE))
        {
            print_r($matches);
			$matches = $token == FROG_STRING ? $matches[1] : $matches[0];
            foreach ($matches as $word)
                $result[$word[1]] = [$token, $word[0]];
        }
    }
    ksort($result);
    $final = [];
    print_r($result);
    foreach ($result as $token)
        array_push($final, $token);
    return $final;
}

function parser($tokens)
{
    $line = 1;
    $statements = [];
    $includes = [];
    $variables = [];
    global $data_types;

    foreach ($tokens as $pos => [$token, $ctx])
    {
        switch ($token)
        {
            case FROG_NL:
                $line++;
                break;
            case FROG_ECHO:
                if (!isset($tokens[$pos+2]))
                {
                    echo "Missing ; on line $line\n"; // TODO ERROR funciton
                    return;
                }

                if ($tokens[$pos+2][0] != FROG_END)
                {
                    echo "Missing ; on line $line\n";
                    return;
                }

                if (!in_array("stdio.h", $includes))
                    array_push($includes, "stdio.h");

				$print_ctx = $tokens[$pos+1][1];
				switch ($tokens[$pos+1][0])
				{
					case FROG_STRING:
						$print = "\"$print_ctx\"";
						break;
					case FROG_INTEGER:
						$print = "\"%i\", $print_ctx";
						break;
					case FROG_VARIABLE:
						$print = $print_ctx;
						break;
					default:
						echo "Unexpected token on line $line. Expected String/Integer/Variable";
						return;
				}
                array_push($statements, 
                    "printf(" . $print . ");"
                );
                break;
            case FROG_STRING:
            case FROG_INTEGER:
                break;
            case FROG_VARIABLE:
                $next = $tokens[$pos+1][0]; 
				if ($next == FROG_END)
					break;
			
				if ($next != FROG_ASSIGN)
				{
					echo "Unexpected token on line $line.";
					return;
                }

				switch ($assign = $tokens[$pos+2][0])
				{
                    case FROG_INTEGER:
                    case FROG_STRING:
                        $variables[$ctx] = [$tokens[$pos+2][0]];
                        array_push($statements, $data_types[$assign] . " " . $ctx . " = " . $tokens[$pos+2][1]);
						break;
					default:
						echo "Unsupported assign value on line $line. Expected String/Variable.";
						return;
				}
				
				
        }
    }

    $result = "";

    foreach ($includes as $lib)
        $result .= "#include<" . $lib . ">\n";

    $result .= "\nint main()\n{\n\t" . implode("\n\t", $statements) . "\n}\n";

    return $result;

}

echo parser(lexer(
    
'

'

));

