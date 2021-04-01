<?php namespace Waka\Utils\Classes\Traits;

trait DbUtils
{
    public function getNextId($stringify = false, $num = 4)
    {
        $statement = \DB::select("SHOW TABLE STATUS LIKE '".$this->table."'");
        $id = $statement[0]->Auto_increment;
        return $id;
    }

    public function getNextStringId($num = 4)
    {
        $id = $this->getNextId();
        return str_pad( $id, $num, "0", STR_PAD_LEFT );
    }
}
