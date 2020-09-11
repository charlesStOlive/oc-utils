<?php namespace Waka\Utils\Classes;

class Scopes
{
    use \Waka\Utils\Classes\Traits\StringRelation;

    public $scopes;
    public $target;
    public $mode;
    public $checked;
    public $checkedOk;

    public function __construct($doc, $target)
    {
        $this->scopes = $doc->scopes;
        $this->target = $target;
        $this->mode = $doc->scope_type;
        trace_log($doc->name);

    }
    public function checkScopes()
    {
        $this->checked = 0;
        $this->checkedOK = 0;

        //s'il n'y a pas de scope on retourne la valeur directement.
        if (!$this->scopes) {
            return true;
        }

        foreach ($this->scopes as $scope) {
            $this->checked++;
            $model = $this->target;
            if (!$scope['self']) {
                $model = $this->getStringModelRelation($model, $scope['target']);
                trace_log($model->name);
            }
            switch ($scope['scopeKey']) {

                case 'model_value':
                    $ck = $this->getSingleValueValidation($model, $scope);
                    if ($ck) {
                        $this->checkedOK++;
                    }

                    break;
                case 'model_values':
                    $ck = $this->getMultipleValueValidation($model, $scope);
                    if ($ck) {
                        $this->checkedOK++;
                    }

                    break;
                case 'model_relation':
                    $ck = $this->getRelationValidation($model, $scope);
                    if ($ck) {
                        $this->checkedOK++;
                    }

                    break;
                case 'model_bool':
                    $ck = $this->getBoolValueValidation($model, $scope);
                    if ($ck) {
                        $this->checkedOK++;
                    }

                    break;
            }
        }
        return $this->checkedOK == $this->checked;
    }
    private function getSingleValueValidation($model, $scope)
    {
        $field = $scope['scope_field'] ?? false;
        if (!$field) {
            return false;
        }
        $valueFromModel = $model[$scope['scope_field']];
        $valueFromScope = $scope['scope_value'];

        return $valueFromModel == $valueFromScope;

    }
    private function getMultipleValueValidation($model, $scope)
    {
        $field = $scope['scope_field'] ?? false;
        $values = $scope['scope_values'] ?? false;
        if (!$field) {
            return false;
        }
        if (!$values) {
            return false;
        }
        if (!$model) {
            trace_log("Erreur sur le modèle");
        }
        if (in_array($model->slug, $values)) {
            return true;
        } else {
            return false;
        }

    }
    private function getRelationValidation($model, $scope)
    {
        $relation = $scope['scope_relation'] ?? false;
        if (!$relation) {
            return false;
        }
        if (!$model->{$relation}) {
            return false;
        }

        return boolVal($model->{$relation}->count());

    }
    private function getBoolValueValidation($model, $scope)
    {
        $field = $scope['scope_field'] ?? false;
        if (!$field) {
            return false;
        }
        $boolFromModel = $model[$scope['scope_field']];
        $boolFromScope = boolval($scope['scope_bool']);

        return $boolFromModel == $boolFromScope;
    }

}
