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
        $value = $this->getConfig('value');
        trace_log($mode);
        trace_log($value);
        // if($mode == "permissions") {
        //     return $user->hasAccess($value);
        // }
        // if($mode == "roleCode") {
        //     return $user->role->code == $value;
        // }
        return true;
    }
}
