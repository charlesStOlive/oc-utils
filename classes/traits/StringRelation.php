<?php namespace Waka\Utils\Classes\Traits;

trait StringRelation
{
    public function getStringRelation($model, String $relation)
    {
        $returnValue = $model;
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

    public function getStringModelRelation($model, String $relation, array $withSubR = null)
    {
        $parts = explode(".", $relation);

        foreach ($parts as $part) {
            // trace_log("part : " . $part);
            // trace_log($model ? 'model OK' : 'model pas ok');
            if ($model) {
                // trace_log("nom du modele " . $model->name);
                $tempModel = $model->{$part};
                if (!$tempModel && $model->methodExists('getThisParentValue')) {
                    $model = $model->getThisParentValue($part);
                } else {
                    $model = $tempModel;
                }
            } else {
                return null;
            }
        }
        return $model;
    }

    public function getStringRequestRelation($model, String $relation, array $withSubR = null)
    {
        $parts = explode(".", $relation);
        $relatedModel = null;

        foreach ($parts as $part) {
            //$relationType = $model->getRelationType($part);
            //trace_log($relationType);
            if (next($parts)) {
                $model = $model->{$part};
            } else {
                $relatedModel = $model->{$part}();
                //trace_log(get_class($relatedModel));
            }
        }
        return $relatedModel;
    }
}
