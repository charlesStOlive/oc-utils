<?php namespace Waka\Utils\Classes\Traits;

trait StringRelation
{
    public function getStringRelation($model, String $relation)
    {
        $returnValue = null;
        $parts = explode(".", $relation);
        $nbParts = count($parts) ?? 1;
        if ($nbParts > 1) {
            if ($nbParts == 2) {
                $returnValue = $model[$parts[0]][$parts[1]] ?? null;
            }

            if ($nbParts == 3) {
                $returnValue = $model[$parts[0]][$parts[1]][$parts[2]] ?? null;
            }

            if ($nbParts == 4) {
                $returnValue = $model[$parts[0]][$parts[1]][$parts[2]] ?? null;
            }

        } else {
            $returnValue = $model[$relation] ?? null;
        }
        return $returnValue;
    }
    public function getStringModelRelation($model, String $relation)
    {
        $returnValue = null;
        $parts = explode(".", $relation);
        $nbParts = count($parts) ?? 1;
        if ($nbParts > 1) {
            if ($nbParts == 2) {
                $returnValue = $model->{$parts[0]}->{$parts[1]} ?? null;
            }

            if ($nbParts == 3) {
                $returnValue = $model->{$parts[0]}->{$parts[1]}->{$parts[2]} ?? null;
            }

            if ($nbParts == 4) {
                $returnValue = $model->{$parts[0]}->{$parts[1]}->{$parts[2]} ?? null;
            }

        } else {
            $returnValue = $model->{$relation} ?? null;
        }
        return $returnValue;
    }
}
