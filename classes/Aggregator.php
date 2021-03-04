<?php namespace Waka\Utils\Classes;

use Lang;
use MathPHP\Algebra;
use MathPHP\Functions\Map;

class Aggregator
{

    public function operate2Vars($type, $val1, $val2)
    {
    }
    public function operate2Rows($val1, $val2)
    {
        $total = Map\Multi::multiply($val1, $val2);
        return array_sum($total);
    }
}
