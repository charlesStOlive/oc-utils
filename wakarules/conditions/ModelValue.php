<?php namespace Waka\Utils\WakaRules\Conditions;

use Waka\Utils\Classes\Rules\RuleConditionBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use Waka\Utils\Interfaces\Rule as RuleInterface;

class ModelValue extends RuleConditionBase implements RuleInterface
{
    use \Waka\Utils\Classes\Traits\StringRelation;
    
    /**
     * Returns information about this event, including name and description.
     */
    public function subFormDetails()
    {
        return [
            'name'        => 'Valeur du modèle',
            'description' => 'Condition lié à une valeur du modèle ou d\'une relation',
            'icon'        => 'icon-value',
            'share_mode' => 'choose',
        ];
    }

    public function getText()
    {
        //trace_log('getText HTMLASK---');
        $hostObj = $this->host;
        $field = $this->getConfig('field');
        $operator = $this->getConfig('operator');
        $value = $this->getConfig('value');
        if($operator == 'existe') {
            $text = 'Le champs  <b>"'.$field.'"</b> doit exister';
        }
        else if($operator == 'existepas') {
            $text = 'Le champs  : <b>"'.$field.'"</b> NE doit PAS exister';
        } else {
             $text = 'Verification : F='.$field.' | O='.$operator.' | V='.$value;
        }
        
        //trace_log($hostObj->config_data);
       
        if($text) {
            return $text;
        }
        return parent::getText();

    }

    /**
     * IS true
     */

    private function check($modelSrc, $context = 'twig', $dataForTwig = []) {
        $field = $this->getConfig('field');
        $operator = $this->getConfig('operator');
        $value = $this->getConfig('value');
        $fieldValue = array_get($modelSrc, $field);
        return $this->compareValue($fieldValue, $operator, $value);
        
    }

    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {
        $ok = $this->check($modelSrc, $context, $dataForTwig);
        if(!$ok) {
            $this->setError();
        }
        return $ok;
    }

    public function compareValue($fieldValue, $operator, $valueSearched) {
        trace_log($fieldValue);
        switch ($operator) {
            case 'existe' :
                return !empty($fieldValue);
             case 'existePas' :
                return empty($fieldValue);
            case 'count' :
                $return = false;
                $isArray = is_array($fieldValue);
                if($isArray) {
                    return count($isArray);
                } 
                return $return;
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
