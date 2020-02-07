<?php namespace Waka\Utils\Classes\fields;

use Lang;

class BaseField {
    
    public $label;
    public $config;
    public $key;
    public $model;
    public $class;
    public $incon;

    public function __construct($model, $key, $config) {
        $this->model = $model;
        $this->key = $key;
        $this->config = $config;
        $this->class = $config['class'] ?? null;
        $this->icon = $config['icon'] ?? false;
        $this->prepareBase(); 
    }
    
    public function prepareBase() {
        $this->label =  $this->setLabel();
        $this->value = $this->parseRelation();
    }

    public function setLabel() {
        if($this->config['labelFrom'] ?? false) {
            $labelFrom = $this->parseRelation($this->config['labelFrom']);
            return Lang::get($labelFrom);
        } else {
            return Lang::get($this->config['label']);
        }
       
    }

    public function parseRelation($valueFrom=null) {
        $returnValue = null;
        $fieldKey = $valueFrom ? $valueFrom : $this->key;
        //trace_log($fieldKey);
        $parts = explode(".", $fieldKey);
        $nbParts = count($parts) ?? 1;
        if($nbParts > 1) {
            if($nbParts == 2 ) $returnValue =  $this->model[$parts[0]][$parts[1]] ?? null;
            if($nbParts == 3 ) $returnValue =  $this->model[$parts[0]][$parts[1]][$parts[2]] ?? null;
            if($nbParts == 4 ) $returnValue =  $this->model[$parts[0]][$parts[1]][$parts[2]] ?? null;
        } else {
            $returnValue =  $this->model[$fieldKey] ?? null;
        }
        return $returnValue;
    }
    public function parseQueryRelation($valueFrom=null) {
        //trace_log("pârse query relation");
        $fieldKey = $valueFrom ? $valueFrom : $this->key;
        //trace_log($fieldKey);
        $parts = explode(".", $fieldKey);
        $nbParts = count($parts) ?? 1;
        if($nbParts > 1) {
            if($nbParts == 2 ) return  $this->model{$parts[0]}->{$parts[1]} ?? null;
            if($nbParts == 3 ) return  $this->model{$parts[0]}->{$parts[1]}->{$parts[2]} ?? null;
        } else {
            return $this->model{$fieldKey} ?? null;
        }
    }
    public function parseQueryRelationList($valueFrom=null) {
       //trace_log("parseQueryRelationList");
        $fieldKey = $valueFrom ? $valueFrom : $this->key;
       //trace_log($fieldKey);
        $parts = explode(".", $fieldKey);
        $nbParts = count($parts) ?? 1;
        if($nbParts > 1) {
            if($nbParts == 2 ) return  $this->model->{$parts[0]}->lists($parts[1]) ?? null;
            if($nbParts == 3 ) return  $this->model{$parts[0]}->{$parts[1]}->lists($parts[2]) ?? null;
        } else {
            return $this->model->get($fieldKey) ?? null;
        }
    }
}