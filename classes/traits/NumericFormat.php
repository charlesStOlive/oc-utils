<?php namespace Waka\Utils\Classes\Traits;


trait NumericFormat
{
    #    Output easy-to-read numbers
    #    by james at bandit.co.nz
    public function k_number($n) {
        // first strip any formatting;
        $n = (0+str_replace(",","",$n));
        // is this a number?
        if(!is_numeric($n)) return false;
        // now filter it;
        return round(($n/1000),1).' K';
    }
}
