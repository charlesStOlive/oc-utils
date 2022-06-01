<?php namespace Waka\Utils\WakaRules\Conditions;

use Waka\Utils\Classes\Rules\RuleConditionBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use Waka\Utils\Interfaces\Rule as RuleInterface;

class ModelExist extends RuleConditionBase implements RuleInterface
{
    use \Waka\Utils\Classes\Traits\StringRelation;
    
    /**
     * Returns information about this event, including name and description.
     */
    public function subFormDetails()
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
        $field = $this->getConfig('field');
        $relation = $this->getConfig('relation');
        $mode = $this->getConfig('checkMode');
        $field = $field ? $field : '*';
        //trace_log($hostObj->config_data);
        $text = "Verification existance valeur : Relat=".$relation.' | Modl='.$mode.' | Field='.$field;
        if($text) {
            return $text;
        }
        return parent::getText();

    }

    /**
     * IS true
     */

    private function check($modelSrc, $context = 'twig', $dataForTwig = []) {
        //trace_log('check model value');
        $field = $this->getConfig('field');
        $relation = $this->getConfig('relation');
        $mode = $this->getConfig('checkMode');
        $model = $modelSrc;

        //trace_log($mode);


        if($mode == 'childs') {
            return $this->getStringRequestRelation($model, $relation)->count();
        } elseif($mode == 'parent') {
            $model = $this->getStringModelRelation($model, $relation);
        }  
        if(!$field) {
            return $model ? true : false;
        } else {
            $check =  $model[$field] ?? false;
            return $check ? true : false;
        }
        
    }

    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {
        $ok = $this->check($modelSrc, $context, $dataForTwig);
        if(!$ok) {
            $this->setError();
        }
        return $ok;
    }
}
