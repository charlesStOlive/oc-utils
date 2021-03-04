<?php namespace Waka\Utils\Classes;

class Scopes
{
    use \Waka\Utils\Classes\Traits\StringRelation;

    public $scopes;
    public $target;
    public $mode;
    public $checked;
    public $checkedOk;

    public function __construct($doc, $target = null)
    {
        $this->scopes = $doc->scopes;
        $this->target = $target;
        $this->mode = $doc->is_scope;
    }
    public function checkScopes()
    {
        if (!$this->target) {
            throw new \ApplicationException("Il manque le modèle cible pour l'analyse des scopes");
        }
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
                case 'user':
                    $ck = $this->getUserValidation($scope);
                    if ($ck) {
                        $this->checkedOK++;
                    }
                    break;
                case 'user_role':
                    $ck = $this->getUserRoleValidation($scope);
                    if ($ck) {
                        $this->checkedOK++;
                    }
                    break;
            }
        }
        return $this->checkedOK == $this->checked;
    }
    public function checkIndexScopes()
    {
        $this->checked = 0;
        $this->checkedOK = 0;

        //s'il n'y a pas de scope on retourne la valeur directement.
        if (!$this->scopes) {
            return true;
        }

        foreach ($this->scopes as $scope) {
            $this->checked++;
            switch ($scope['scopeKey']) {
                case 'user':
                    $ck = $this->getUserValidation($scope);
                    if ($ck) {
                        $this->checkedOK++;
                    }
                    break;
                case 'userGroup':
                    $ck = $this->getUserValidation($scope);
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
            //trace_log("Erreur sur le modèle");
        }

        if (in_array($model[$field], $values)) {
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

    private function getUserValidation($scope)
    {
        $userId = \BackendAuth::getUser()->id;

        return in_array($userId, $scope);
    }
    private function getUserRoleValidation($scope)
    {
        $userRoleId = \BackendAuth::getUser()->role_id;
        return in_array($userRoleId, $scope);
    }
}
