<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;
use Waka\Utils\Classes\DataSource;
use Yaml;

class SidebarAttributes extends WidgetBase
{
    use \Waka\Utils\Classes\Traits\StringRelation;
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'attributes';

    public $model;
    public $dataSource;
    public $type;
    public $text_info;
    public $valueArray;
    public $lang_fields;

    public function init()
    {
        $this->fillFromConfig([
            'model',
            'type',
            'text_info',
            'lang_fields',
        ]);
    }

    /**
     * Prepares the form widget view data
     */
    public function render()
    {
        if($this->model->no_ds) {
            return $this->makePartial('empty');
        }
        $this->dataSource = \DataSources::find($this->model->data_source);

        $this->vars['text_info'] = $this->text_info;
        $this->vars['attributesArray'] = $this->getWattributes();
        //$fncArray = $this->getFNCOutputs();
        //$this->vars['FNCSArray'] = $fncArray;

        if ($this->type == 'word') {
            return $this->makePartial('list_word');
        } else {
            return $this->makePartial('list');
        }
    }

    public function getWattributes()
    {
        $attributeArray = [];

        $attributesConfig = $this->dataSource->getAttributesConfig();
        $maped = $this->remapAttributes($attributesConfig['attributes'], 'ds');
        $attributeArray[$this->dataSource->code]['values'] = $maped;
        $attributeArray[$this->dataSource->code]['icon'] = $attributesConfig['icon'];;
        if ($this->dataSource->relations) {
            foreach ($this->dataSource->relations as $key => $relation) {
                //trace_log("key ".$key);
                $ex = explode('.', $key);
                $relationcode = array_pop($ex);
                //trace_log("Relation name : ".$relationName);
                trace_log(\DataSources::list());
                $relationAttributesConfig = \DataSources::find($relationcode)->getAttributesConfig();
                trace_log($relationcode);
                trace_log($relationAttributesConfig);
                $maped = $this->remapAttributes($relationAttributesConfig['attributes'], $key, 'ds');
                $attributeArray[$relationcode]['values'] = $maped;
                $attributeArray[$relationcode]['icon'] = $relationAttributesConfig['icon'];
            }
            //trace_log($attributeArray);
        }
        return $attributeArray;
    }

    public function remapAttributes(array $attributes, $relationOrName, $name = null, $row = false)
    {
        $transformers = \Config::get('waka.utils::transformers');
        $documentType = 'twig';
        if ($this->type == 'word') {
            $documentType = 'word';
            $row = false;
        }

        $mapedResult = [];
        foreach ($attributes as $key => $attribute) {
            //trace_log($attribute);
            $type = $attribute['type'] ?? null;
            $label = $attribute['label'] ?? null;

            //Gestion du keyName
            $KeyName;
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

    // public function getIMG()
    // {
    //     //trace_log(get_class($this->model));
    //     $imgs = $this->model->images;

    //     if (!$imgs) {
    //         return [];
    //     }
    //     $result = [];

    //     //remap images
    //     $remapImages = [];
    //     foreach ($imgs as $key => $img) {
    //         $remapImages[$img['code']] = [
    //             'label' => $img['code'],
    //             'type' => 'modelImage',
    //         ];
    //     }
    //     $attributesImg = $this->remapAttributes($remapImages, 'modelImage');
    //     return $attributesImg;
    // }

    // public function getFNCOutputs()
    // {

    //     $fncs = $this->model->model_functions;
    //     if (!$fncs) {
    //         return [];
    //     }
    //     $result = [];
    //     foreach ($fncs as $fnc) {
    //         $code = $fnc['collectionCode'];
    //         $outputs = $this->dataSource->getFunctionsOutput($fnc['functionCode']);
    //         //trace_log($outputs);
    //         if ($outputs) {
    //             $attributes = $outputs['attributes'] ?? null;
    //             if ($attributes) {
    //                 $temptAttributeArray = [];
    //                 foreach ($attributes as $key => $attributeAdresse) {
    //                     //trace_log($key);
    //                     $attributeArray = Yaml::parseFile(plugins_path() . '/' . $attributeAdresse);
    //                     if ($key == "main") {
    //                         $maped = $this->remapAttributes($attributeArray['attributes'], $code, null, true);
    //                     } else {
    //                         $maped = $this->remapAttributes($attributeArray['attributes'], $key, $code, true);
    //                     }
    //                     $temptAttributeArray = array_merge($temptAttributeArray, $maped);
    //                 }
    //                 $result[$code] = $temptAttributeArray;
    //             }
    //             $values = $outputs['values'] ?? null;
    //             if ($values) {
    //                 //trace_log($values);
    //                 $maped = $this->remapAttributes($values, $code, null, true);
    //                 if ($result[$code] ?? null) {
    //                     $result[$code] = array_merge($result[$code], $maped);
    //                 } else {
    //                     $result[$code] = $maped;
    //                 }
    //             }
    //             //trace_log("result");
    //             //trace_log($result);
    //         }
    //     }

    //     return $result;
    // }

    public function loadAssets()
    {
        $this->addCss('css/sidebarattributes.css', 'Waka.Utils');
        $this->addJs('js/clipboard.min.js', 'Waka.Utils');
    }
}
