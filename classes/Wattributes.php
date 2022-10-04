<?php namespace Waka\Utils\Classes;

use Winter\Storm\Support\Collection;

class Wattributes
{

    public $dataSource;
    public $ds;
    public $model;
    public $mode;
    public $btnsConfig;
    private $typeTransformers;
    public function __construct($model, $mode)
    {
        $this->dataSource = null;
        $this->model = $model;
        $this->mode = $mode;
        if($this->model->waka_session?->data_source) {
            $this->dataSource = \DataSources::find($this->model->waka_session->data_source);
        } 
        $this->typeTransformers = \Config::get('waka.utils::transformers');
        $this->btnsConfig = \Config::get('waka.utils::transformers.add.'.$this->mode);
        
    }
    public function getWAttributes()
    {
        if(!$this->dataSource) {
            return [];
        }
        //trace_log("lancement de getAttributes");
        $attributeArray = [];

        $attributesConfig = $this->dataSource->getAttributesConfig();
        $maped = $this->remapAttributes($attributesConfig['attributes'], 'ds');
        $attributeArray[$this->dataSource->code]['values'] = $maped;
        $attributeArray[$this->dataSource->code]['icon'] = $attributesConfig['icon'];;
        if ($this->dataSource->relations) {
            //trace_log($this->dataSource->relations);
            foreach ($this->dataSource->relations as $key => $relation) {
                //trace_log("key ".$key);
                $ex = explode('.', $key);
                $relationcode = array_pop($ex);
                //trace_log("Relation name : ".$relationName);
                //trace_log(\DataSources::list());
                $relationAttributesConfig = \DataSources::find($relationcode)->getAttributesConfig();
                //trace_log($relationcode);
                //trace_log($relationAttributesConfig);
                $maped = $this->remapAttributes($relationAttributesConfig['attributes'], $key, 'ds');
                $attributeArray[$relationcode]['values'] = $maped;
                $attributeArray[$relationcode]['icon'] = $relationAttributesConfig['icon'];
            }
            //trace_log($attributeArray);
        }
        return $attributeArray;
    }

    

    private function remapAttributes(array $attributes, $relationOrName, $name = null, $row = false)
    {
        $transformers = \Config::get('waka.utils::transformers');
        $documentType = 'twig';
        if ($this->mode == 'word') {
            $documentType = 'word';
            $row = false;
        }

        $mapedResult = [];
        foreach ($attributes as $key => $attribute) {
            //trace_log($attribute);
            $type = $attribute['type'] ?? null;
            $label = $attribute['label'] ?? null;

            //Gestion du keyName
            $KeyName = '';
            if ($relationOrName == "modelImage") {
                $KeyName = $key;
            } elseif ($row && $name) {
                $KeyName = 'row.' . $relationOrName . '.' . $key;
            } elseif ($row && !$name) {
                $KeyName = 'row.' . $key;
            } elseif ($name) {
                $KeyName = $name . '.' . $relationOrName . '.' . $key;
            } else {
                $KeyName = $relationOrName . '.' . $key;
            }
            //Application de la transformation
            //trace_log("type : " . $type . " | " . $KeyName);
            if ($type) {
                $transformer = $transformers['types'][$type][$documentType] ?? null;
                //trace_log($transformer);
                if ($transformer) {
                    $KeyName = sprintf($transformer, $KeyName);
                } else {
                    $documentTypeTransformer = $transformers[$documentType];
                    $KeyName = sprintf($documentTypeTransformer, $KeyName);
                }
            } else {
                $documentTypeTransformer = $transformers[$documentType];
                $KeyName = sprintf($documentTypeTransformer, $KeyName);
            }
            $mapedResult[$KeyName] = $label;
        }
        //trace_log($mapedResult);
        return $mapedResult;
    }

    public function getAsks($asks)
    {
        if (!$asks) {
            return [];
        }
        $result = [];
        //trace_log($this->mode);
        foreach ($asks as $ask) {
            $format = '';
            $outputFormat =  $ask->getOutputTypes($this->mode);   
            if($outputFormat) {
                $format = $this->typeTransformers['types'][$outputFormat][$this->mode];
            } else {
                $format = $this->typeTransformers[$this->mode];
            }

            $value = sprintf($format, 'asks.'.$ask->code);
            // $value = $ask->code;
            $result[$value] = $value;
        }

        return $result;
    }

    public function getFncOutput($fnc)
    {
        if(!$this->dataSource) {
            return [];
        }
        $code = $fnc->getCode();
        $result = [];
        //
        $outputConfig = $fnc->getOutputs();
        //trace_log("code : -----------------".$code);
        //trace_log($outputConfig);
        if ($outputAttributes = $outputConfig['attributes'] ?? false) {
                $tempAttributeArray = [];
                foreach ($outputAttributes as $key => $attributeAdresse) {
                    //trace_log('attributeArray : '.$key);
                    $attributeArray = \Yaml::parseFile(plugins_path() . '/' . $attributeAdresse);

                    if ($key == "main") {
                        $maped = $this->remapAttributes($attributeArray['attributes'], $code, null, true);
                    } else {
                        $maped = $this->remapAttributes($attributeArray['attributes'], $key, $code, true);
                    }
                    $tempAttributeArray = array_merge($tempAttributeArray, $maped);
                }
                $result[$code] = $tempAttributeArray;
            $values = $outputs['values'] ?? null;
        }
        if ($outputValues = $outputConfig['values'] ?? false) {
            $maped = $this->remapAttributes($outputValues, $code, null, true);
            if ($result[$code] ?? null) {
                $result[$code] = array_merge($result[$code], $maped);
            } else {
                $result[$code] = $maped;
            }
        }

        //création des boutons par défauts
        $starters = [];
        $btns = $this->btnsConfig;
        $btnFull = null;
        
        foreach($btns as $key=>$btn) {
            //trace_log($btn);
            //trace_log($code);
            $myValue = sprintf($btn, $code);
            $starters[$myValue] =  $key;
            $btnFull .= sprintf($btn, $code)."\n";
        }
        $starters[$btnFull] =  'Fonction complète';
        $result[$code]['starters'] =  $starters;
        //trace_log($result);
        return $result;
    }

    public function getManuelFncOutput($attributeAdresse, $code) {
        $outputConfig = \Yaml::parseFile(plugins_path() . '/' . $attributeAdresse);
        $result = [];
        $outputConfig = $outputConfig['outputs'] ?? [];
        if ($outputAttributes = $outputConfig['attributes'] ?? false) {
            $tempAttributeArray = [];
            foreach ($outputAttributes as $key => $attributeAdresse) {
                //trace_log('attributeArray : '.$key);
                $attributeArray = \Yaml::parseFile(plugins_path() . '/' . $attributeAdresse);

                if ($key == "main") {
                    $maped = $this->remapAttributes($attributeArray['attributes'], $code, null, true);
                } else {
                    $maped = $this->remapAttributes($attributeArray['attributes'], $key, $code, true);
                }
                $tempAttributeArray = array_merge($tempAttributeArray, $maped);
            }
            $result[$code] = $tempAttributeArray;
        }
        if ($outputValues = $outputConfig['values'] ?? false) {
            $maped = $this->remapAttributes($outputValues, $code, null, true);
            if ($result[$code] ?? null) {
                $result[$code] = array_merge($result[$code], $maped);
            } else {
                $result[$code] = $maped;
            }
        }
        return [
            'fonctions' => [
                'values' => $result['row'],
                'icon' => ' oc-icon-code',
            ],
        ];
    }

    public function getFncsOutputs($fncs)
    {
        if (!$fncs) {
            return [];
        }
        $result = [];
        foreach ($fncs as $fnc) {
            $outputFnc = $this->getFncOutput($fnc);
            $result = array_merge($result, $outputFnc);
        }

        return $result;
    }




}
