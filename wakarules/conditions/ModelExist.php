<?php namespace Waka\Utils\WakaRules\Conditions;

use Waka\Utils\Classes\Rules\RuleConditionBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use Waka\Utils\Interfaces\Rule as RuleInterface;

class ModelExist extends RuleConditionBase implements RuleInterface
{
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

    private function check($modelSrc) {
        //trace_log('check model value');
        $field = $this->getConfig('field');
        $relation = $this->getConfig('relation');
        if($relation)  $field = $relation.'.'.$field;
        //$mode = $this->getConfig('checkMode');
        $mode  = 'existe';
        // $model = $modelSrc;

        //trace_log($mode);
        $values = array_get($modelSrc, $field);
        //trace_log($modelSrc);
        //trace_log($field);
        //trace_log($values);

        if($mode ==  "existe") {
            if($values) {
                return true;
            } else {
                return false;
            }
        }

        if($mode ==  "countChild") {
            if($values->count()) {
                return true;
            } else {
                return false;
            }
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
