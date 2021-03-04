<?php namespace Waka\Utils\Columns;

use Backend\Classes\ListColumn;
use Lang;
use Model;

class CalculColumn
{
    /**
     * @param            $value
     * @param ListColumn $column
     * @param Model      $record
     *
     * @return string HTML
     */
    public static function render($value, ListColumn $column, Model $record)
    {
        $field = new self($value, $column, $record);
        return $field->getcalcul();
    }

    /**
     * ListSwitchField constructor.
     *
     * @param            $value
     * @param ListColumn $column
     * @param Model      $record
     */
    public function __construct($value, ListColumn $column, Model $record)
    {
        $this->name = $column->columnName;
        $this->value = $value;
        $this->column = $column;
        $this->record = $record;
    }

    public function getCalcul()
    {
        $config = $this->column->config;
        
        $var1 = $config['var1'];
        $var2 = $config['var2'];
        if (!is_numeric($var1)) {
            $var1 = $this->getModelValue($var1);
        }
        if (!is_numeric($var2)) {
            $var2 = $this->getModelValue($var2);
        }
        $operator = $config['operator'];
        $result = 0;
        switch ($operator) {
            case 'add':
                $result = $var1 + $var2;
                break;
            case 'multiply':
                $result = $var1 * $var2;
                break;
            case 'divide':
                $result = $var1 / $var2;
                break;
            case 'substract':
                $result = $var1 - $var2;
                break;
        }
        return $result;
    }

    private function getModelValue($attribute = null)
    {
        return $this->record[$attribute];
    }
}
