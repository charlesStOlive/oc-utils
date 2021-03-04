<?php namespace Waka\Utils\Classes;

class ReverseLogArray
{
    /**
     * @param string $output : The output of a multiple print_r calls, separated by newlines
     * @return mixed[] : parseable elements of $output
     */
    public static function print_r_reverse_multiple($output)
    {
        $result = array();
        while (($reverse = _self::print_r_reverse($output)) !== null) {
            $result[] = $reverse;
        }
        return $result;
    }

    public static function print_r_reverse($output)
    {
        $expecting = 0; // 0=nothing in particular, 1=array open paren '(', 2=array element or close paren ')'
        $lines = explode("\n", $output);
        $result = null;
        $topArray = null;
        $arrayStack = array();
        $matches = null;
        while (!empty($lines) && $result === null) {
            $line = array_shift($lines);
            $trim = trim($line);
            if ($trim == 'Array') {
                if ($expecting == 0) {
                    $topArray = array();
                    $expecting = 1;
                } else {
                    trigger_error("Unknown array.");
                }
            } elseif ($expecting == 1 && $trim == '(') {
                $expecting = 2;
            } elseif ($expecting == 2 && preg_match('/^\[(.+?)\] \=\> (.+)$/', $trim, $matches)) { // array element
                list($fullMatch, $key, $element) = $matches;
                if (trim($element) == 'Array') {
                    $topArray[$key] = array();
                    $newTopArray = &$topArray[$key];
                    $arrayStack[] = &$topArray;
                    $topArray = &$newTopArray;
                    $expecting = 1;
                } else {
                    $topArray[$key] = $element;
                }
            } elseif ($expecting == 2 && $trim == ')') { // end current array
                if (empty($arrayStack)) {
                    $result = $topArray;
                } else // pop into parent array
                {
                    // safe array pop
                    $keys = array_keys($arrayStack);
                    $lastKey = array_pop($keys);
                    $temp = &$arrayStack[$lastKey];
                    unset($arrayStack[$lastKey]);
                    $topArray = &$temp;
                }
            }
            // Added this to allow for multi line strings.
            elseif (!empty($trim) && $expecting == 2) {
                // Expecting close parent or element, but got just a string
                $topArray[$key] .= "\n" . $line;
            } elseif (!empty($trim)) {
                $result = $line;
            }
        }

        $output = implode("\n", $lines);
        return $result;
    }
}
