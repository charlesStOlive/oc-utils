<?php namespace Waka\Utils\Classes\fields;

class LabelCalcul extends BaseField
{
    public $partial = 'label_attribute';
    public $total;

    public function __construct($model, $key, $config)
    {
        parent::__construct($model, $key, $config);
        $this->total = $this->getValue();
    }

    public function getValue()
    {
        $val1 = array_get($this->model, $this->config['row_var1']);
        $val2 = array_get($this->model, $this->config['row_var2']);

        $operator = $this->config['operator'];

        switch ($operator) {
            case 'add':
                return $this->value = $val1 + $val2;
                break;
            case 'substract':
                return $this->value = $val1 - $val2;
                break;
            case 'divide':
                return $this->value = $val1 / $val2;
                break;
            case 'multiply':
                return $this->value = $val1 * $val2;
                break;
        }
    }
}
