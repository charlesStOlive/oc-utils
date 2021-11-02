<?php namespace Waka\Utils\WakaRules\Conditions;

use Waka\Utils\Classes\Rules\RuleConditionBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;

class BackUser extends RuleConditionBase
{
    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function ruleDetails()
    {
        return [
            'name'        => 'Administrateur',
            'description' => 'Condition lié à l\'administrateur connecté ( rôle, groupe )',
            'icon'        => 'icon-user',
            'premission'  => 'wcli.utils.cond.edit.admin',
        ];
    }

    public function getText()
    {
        //trace_log('getText HTMLASK---');
        $hostObj = $this->host;
        //trace_log($hostObj->config_data);
        $text = $hostObj->config_data['txt'] ?? null;
        if($text) {
            return $text;
        }
        return parent::getText();

    }

    /**
     * IS true
     */

    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {
        // if(\App::runningInConsole()) {
        //     return true;
        // }
        $user = \BackendAuth::getUser();
        $mode = $this->getConfig('mode');
        $operator = $this->getConfig('operator');
        $value = $this->getConfig('value');
        if($mode == "permissions") {
            return $this->comparePermissions($user, $operator, $value);
        }
        if($mode == "roleCode") {
            return $this->compareRole($user->role->code, $operator, $value);
        }
        //return true;
    }

    public function comparePermissions($user, $operator, $value) {
        switch ($operator) {
            case 'where' :
                return $user->hasAccess($value);
            case 'whereNot' :
                return !$user->hasAccess($value);;
            case 'wherein' :
                return $user->hasAccess([$value]);
            case 'whereNotIn' :
                return !$user->hasAccess([$value]);
        }
    }

    public function compareRole($code, $operator, $value) {
        switch ($operator) {
            case 'where' :
                return $code == $value;
            case 'whereNot' :
                //trace_log('whereNot : '.$code != $value);
                return $code != $value;
            case 'wherein' :
                return in_array($code, [$value]);
            case 'whereNotIn' :
                return !in_array($code, [$value]);
        }
    }
}
