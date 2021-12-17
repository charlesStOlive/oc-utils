<?php namespace Waka\Utils\Classes;

class Conditions
{
    public $conditions;
    public $target;
    public $productoreName;
    public $checked;
    public $checkedOk;
    private $logs;


    public function __construct($productor, $model = null)
    {
        $this->conditions = $productor->rule_conditions;
        $this->productoreName = $productor->name ?? "Producteur Inconnu";
        $this->model = $model;
        $this->mode = $productor->is_scope;
        $this->logs = [];
    }
    public function checkConditions()
    {
        if (!$this->model) {
            throw new \ApplicationException("Il manque le modèle cible pour l'analyse des conditions");
        }
        

        //s'il n'y a pas de scope on retourne la valeur directement.
        if (!$this->hasConditions()) {
            return true;
        }

        $this->checked = 0;
        $this->checkedOk = 0;
        foreach ($this->conditions as $condition) {
            //trace_log($condition->toArray());
            if($condition->resolve($this->model)) {
                $this->checkedOk++;
                //trace_log('ok');
            } else {
                //trace_log('pas ok : '.$this->productoreName);
                $this->setLogs($this->productoreName, $condition->getError());
            }
            
            $this->checked++;
        }
        return $this->checked == $this->checkedOk;
    }
    public function hasConditions() {
        if(!$this->conditions) {
            return false;
        } elseif(!$this->conditions->count()) {
            return false;
        } else {
            return true;
        }
        
    }
    public function getLogs() {
        return $this->logs;

    }
    public function setLogs($id, $error) {
        array_push($this->logs, [
            'id' => $id,
            'error' => $error,
        ]);
    }
    
}
