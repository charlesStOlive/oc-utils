<?php namespace Waka\Utils\Classes;

class BaseFunction
{
    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getFunctionsList()
    {
        $data = [];
        $functions = $this->listFunctionAttributes();
        foreach ($functions as $key => $values) {
            $data[$key] = $values['name'];
        }
        return $data;
    }
    public function getFunctionAttribute($value)
    {
        $functions = $this->listFunctionAttributes();
        foreach ($functions as $key => $values) {
            if ($key == $value) {
                return $values['attributes'] ?? null;
            }
        }

    }

}
