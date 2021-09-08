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
        return Yaml::parseFile(plugins_path() . '/wcli/wconfig/functions/' . $yamlFile);
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
    public function getFunctionAttribute($functionCode)
    {
        $functions = $this->listFunctionAttributes();
        return $this->findFunction($functions, $functionCode, 'attributes');
    }

    public function getFunctionsOutput($value)
    {
        $functions = $this->listFunctionAttributes();
        try {
            $outputs = $this->findFunction($functions, $value, 'outputs');
            return $outputs;
        } catch( Exeption $e) {
            return null;

        }
        
        
    }

    private function findFunction($functions, $functionCode, $searchedKey)
    {
        //trace_log($functions);
        $functionName = $functions[$functionCode] ?? null;
        if (!$functionName) {
            //throw new \ApplicationException("La fonction d'édition  : ".$functionCode." n'existe pas");
        }
        $atttributes = $functions[$functionCode][$searchedKey] ?? [];
        $valeursExtended = [];
        $valeurs = [];
        // if (!$atttributes) {
        //     throw new \ApplicationException("Erreur attributs de la fonction : ".$functionCode." :  ".$searchedKey);
        // }
        if ($atttributes['extend'] ?? false) {
            $extendedFunction = $atttributes['extend'];
            $atttributesExtended = $functions[$extendedFunction][$searchedKey] ?? null;
            if (!$atttributesExtended) {
                throw new \SystemException("Erreur attributs d'une fonction d'édition étendu");
            }
            $valeursExtended = $this->recursiveSearchDynamicValue($atttributesExtended);
            unset($atttributes['extend']);
        }
        $valeurs = $this->recursiveSearchDynamicValue($atttributes);

        return array_merge($valeursExtended, $valeurs);
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
                } elseif (starts_with($tempValue, 'config::')) {
                    $configName = str_replace('config::waka', "waka", $tempValue);
                    $tempValue = \Config::get($configName);
                } elseif (starts_with($tempValue, 'list::')) {
                    $className = str_replace('list::', "", $tempValue);
                    $tempValue = $className::lists('name', 'id');
                }
                $returnArray[$key] = $tempValue;
            }
        }
        return $returnArray;
    }

    /**
     * Méthode pour ajouter des attributs à un array.
     */
    public function getAttributesDs($model)
    {
        return $model->map(function ($item) {
            $atts = $item->attributesToDs;
            foreach ($atts as $att) {
                $item->append($att);
            }
            return $item;
        });
    }

    public function getCalculConfig($label = "Opérateur", $span = "left")
    {
        return [
            'label' => $label,
            'type' => 'dropdown',
            'span' => $span,
            'options' => [
                '=' => "égale à",
                '>' => "supérieur à",
                '<' => 'inférieur à',
                '>=' => "supérieur ou égale à",
                '<=' => "inférieur ou égale à",
            ],
        ];
    }

    function dynamic_comparison ($var1, $op, $var2) {
        switch ($op) {
            case "=":  return $var1 == $var2;
            case "!=": return $var1 != $var2;
            case ">=": return $var1 >= $var2;
            case "<=": return $var1 <= $var2;
            case ">":  return $var1 >  $var2;
            case "<":  return $var1 <  $var2;
            default:       return true;
        }   
    }
}
