<?php namespace Waka\Utils\Classes;

class Conditions
{
    public $conditions;
    public $target;
    public $mode;
    public $checked;
    public $checkedOk;
    private $logs = [];

    public function __construct($productor, $model = null)
    {
        $this->conditions = $productor->rule_conditions;
        $this->model = $model;
        $this->mode = $productor->is_scope;
    }
    public function checkConditions()
    {
        if (!$this->model) {
            throw new \ApplicationException("Il manque le modèle cible pour l'analyse des conditions");
        }
        $this->checked = 0;
        $this->checkedOK = 0;

        //s'il n'y a pas de scope on retourne la valeur directement.
        if (!$this->hasConditions()) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            //trace_log($condition->toArray());
            if($condition->resolve($this->model)) {
                $this->checkedOK++;
                trace_log('ok');
            } else {
                trace_log('pas ok');
                //$this->setLogs($this->model->id, 'error');
            }
            
            $this->checked++;
        }
        return $this->checked == $this->checkedOk;
    }
    public function hasConditions() {
        return $this->conditions->count();
    }
    public function getLogs() {

    }
    public function setLogs($id, $reason) {
        array_push($this->logs, [
            'id' => $id,
            'reason' => $reson,
        ]);

        
    }
    
}
