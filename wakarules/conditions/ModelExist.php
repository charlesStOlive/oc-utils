<?php namespace Waka\Utils\WakaRules\Conditions;

use Waka\Utils\Classes\Rules\RuleConditionBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;

class ModelExist extends RuleConditionBase
{
    use \Waka\Utils\Classes\Traits\StringRelation;
    
    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function ruleDetails()
    {
        return [
            'name'        => 'Valeur du modèle existe',
            'description' => 'Condition lié à l\'existance d\'une valeur du modèle ou d\'une relation',
            'icon'        => 'icon-value',
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
        //trace_log('check model value');
        $field = $this->getConfig('field');
        $relation = $this->getConfig('relation');
        $mode = $this->getConfig('mode');
        $model = $modelSrc;
        if($mode == 'parent') {
            $model = $this->getStringModelRelation($model, $relation);
        } elseif($mode == 'childs') {
            return $this->getStringRequestRelation($model, $relation)->count();
        } 
        //trace_log($mode);
        //trace_log($relation);
        //trace_log($field);
        //trace_log($model);
        if($model['iland_3d'] ?? false) {
            return true;
        } else {
            return false;
        }
        
    }
}
