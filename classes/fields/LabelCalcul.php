<?php namespace Waka\Utils\Classes\fields;

use Waka\Utils\Classes\Aggregator;

class LabelCalcul extends BaseField {
    public $partial  = 'label_attribute';
    public $total;

    public function __construct($model, $key, $config) {
        parent::__construct($model, $key, $config);
        $this->total = $this->getValue();
    }

    public function getValue() {
        $val1 = $this->parseQueryRelationList($this->config['row_var1']);
        $val2 = $this->parseQueryRelationList($this->config['row_var2']);
        $operator = $this->config['operator'];
        $calcul = new Aggregator();
        $total = $calcul->operate2Rows($val1, $val2);
        trace_log($total);
        return $this->value = $total;

    }
}