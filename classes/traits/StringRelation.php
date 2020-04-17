<?php namespace Waka\Utils\Classes\Traits;

trait StringRelation
{
    public function getStringRelation($model, String $relation)
    {
        //trace_log("getStringModelRelation : " . $relation);
        $returnValue = null;
        $parts = explode(".", $relation);
        if ($parts[0] ?? false) {
            //trace_log("part0 " . $parts[0]);
            $returnValue = $model[$parts[0]] ?? null;
        }
        if ($parts[1] ?? false) {
            // trace_log("part1 " . $parts[1]);
            // trace_log($returnValue->toArray());
            $returnValue = $returnValue[$parts[1]] ?? null;
        }
        if ($parts[2] ?? false) {
            $returnValue = $returnValue[$parts[2]] ?? null;
        }
        if ($parts[3] ?? false) {
            $returnValue = $returnValue[$parts[3]] ?? null;
        }
        if ($parts[4] ?? false) {
            $returnValue = $returnValue[$parts[4]] ?? null;
        }
        return $returnValue;
    }

    public function getStringModelRelation($model, String $relation)
    {
        //trace_log("getStringModelRelation : " . $relation);
        $returnValue = null;
        $parts = explode(".", $relation);
        $nbParts = count($parts) ?? 1;
        if ($parts[0] ?? false) {
            $returnValue = $model->{$parts[0]} ?? null;
        }
        if ($parts[1] ?? false) {
            $returnValue = $returnValue->{$parts[1]} ?? null;
        }
        if ($parts[2] ?? false) {
            $returnValue = $returnValue->{$parts[2]} ?? null;
        }
        if ($parts[3] ?? false) {
            $returnValue = $returnValue->{$parts[3]} ?? null;
        }
        if ($parts[4] ?? false) {
            $returnValue = $returnValue->{$parts[4]} ?? null;
        }
        return $returnValue;
    }
}
