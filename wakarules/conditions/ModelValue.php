<?php namespace Waka\Utils\WakaRules\Conditions;

use Waka\Utils\Classes\Rules\RuleConditionBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;

class ModelValue extends RuleConditionBase
{
    use \Waka\Utils\Classes\Traits\StringRelation;
    
    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function ruleDetails()
    {
        return [
            'name'        => 'Valeur du modèle',
            'description' => 'Condition lié à une valeur du modèle ou d\'une relation',
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
        trace_log('check model value');
        $mode = $this->getConfig('mode');
        
        $relation = $this->getConfig('relation');
        $model = $modelSrc;
        if($mode == 'parent') {
            $model = $this->getStringModelRelation($model, $relation);
        } 
        $field = $this->getConfig('field');
        $operator = $this->getConfig('operator');
        $value = $this->getConfig('value');

        $fieldValue = $model->{$field};

        trace_log($fieldValue);
        trace_log($operator);
        trace_log($value);
        trace_log("result : ".$this->compareValue($fieldValue, $operator, $value));
        

        return $this->compareValue($fieldValue, $operator, $value);
        
    }

    public function compareValue($fieldValue, $operator, $valueSearched) {
        switch ($operator) {
            case 'where' :
                return $fieldValue == $valueSearched;
            case 'whereNot' :
                return $fieldValue != $valueSearched;
            case 'wherein' :
                return in_array($fieldValue, [$valueSearched]);
            case 'whereNotIn' :
                return !in_array($fieldValue, [$valueSearched]);
        }
    }
}
