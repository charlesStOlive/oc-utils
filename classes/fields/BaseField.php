<?php namespace Waka\Utils\Classes\fields;

use Lang;

class BaseField
{
    public $label;
    public $config;
    public $key;
    public $model;
    public $class;
    public $incon;

    public function __construct($model, $key, $config)
    {
        $this->model = $model;
        $this->key = $key;
        $this->config = $config;
        $this->class = $config['class'] ?? null;
        $this->icon = $config['icon'] ?? false;
        $this->prepareBase();
    }

    public function prepareBase()
    {
        $this->label = $this->setLabel();
        $this->value = $this->parseRelation();
    }

    public function setLabel()
    {
        if ($this->config['labelFrom'] ?? false) {
            $labelFrom = $this->parseRelation($this->config['labelFrom']);
            return Lang::get($labelFrom);
        } else {
            return Lang::get($this->config['label']);
        }
    }

    public function parseRelation($valueFrom = null)
    {
        $fieldKey = $valueFrom ? $valueFrom : $this->key;
        return array_get($this->model, $fieldKey);
    }
}
