<?php namespace Waka\Utils\Classes;

use Yaml;

class FunctionsBase
{
    public $model;

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function listFunctionAttributes()
    {
        $yamlFile = class_basename($this) . '.yaml';
        return Yaml::parseFile(plugins_path() . '/waka/wconfig/functions/' . $yamlFile);
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
        return $this->findFunction($functions, $value, 'attributes');

    }
    public function getFunctionsOutput($value)
    {
        $functions = $this->listFunctionAttributes();
        $outputs = $this->findFunction($functions, $value, 'outputs');
        return $outputs;
    }
    private function findFunction($functions, $value, $column)
    {
        foreach ($functions as $key => $values) {
            if ($key == $value) {
                $array = $values[$column] ?? [];
                return $this->recursiveSearchDynamicValue($array);
            }
        }

    }

    private function recursiveSearchDynamicValue(array $array)
    {
        $returnArray = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                //trace_log("array");
                $returnArray[$key] = $this->recursiveSearchDynamicValue($value);
            } else {
                //on regarde si il y a une valeur dynamique
                $tempValue = $value;
                if (starts_with($tempValue, 'fnc::')) {
                    $fncName = str_replace('fnc::', "", $tempValue);
                    if (method_exists($this, $fncName)) {
                        $tempValue = $this->{$fncName}();
                    } else {
                        throw new \SystemException("La méthode " . $fncName . " n'esixte pas dans la fonction d'édition");
                    }
                } else if (starts_with($tempValue, 'config::')) {
                    $configName = str_replace('config::', "", $tempValue);
                    $tempValue = \Config::get($configName);
                } else if (starts_with($tempValue, 'list::')) {
                    $className = str_replace('list::', "", $tempValue);
                    $tempValue = $className::lists('name', 'id');
                }
                $returnArray[$key] = $tempValue;
            }
        }
        return $returnArray;
    }

}
