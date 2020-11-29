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
    public $separate_fields;
    public $text_info;
    public $hidden_fields;
    public $valueArray;
    public $lang_fields;

    public function init()
    {
        $this->fillFromConfig([
            'model',
            'type',
            'text_info',
            'separate_fields',
            'hidden_fields',
            'lang_fields',
        ]);
    }

    public function render()
    {
        //trace_log('render');
        $this->dataSource = new DataSource($this->model->data_source_id, 'id');

        $this->vars['text_info'] = $this->text_info;
        $this->vars['attributesArray'] = $this->getWattributes();
        $this->vars['IMGSArray'] = $this->getIMG();
        $fncArray = $this->getFNCOutputs();
        $this->vars['FNCSArray'] = $fncArray;

        if ($this->type == 'word') {
            return $this->makePartial('list_word');
        } else {
            return $this->makePartial('list');
        }

    }

    public function getWattributes()
    {
        $attributeArray = [];
        $pluginName = strtolower($this->dataSource->author . '/' . $this->dataSource->plugin . '\/models');
        $attributesPath = plugins_path() . '/' . $pluginName . '/' . $this->dataSource->name . '/attributes.yaml';
        $attributes;
        if (file_exists($attributesPath)) {
            $attributes = Yaml::parseFile($attributesPath);
        } else {
            $modelAttributeAdresse = $this->dataSource->attributesConfig;
            $attributes = Yaml::parseFile(plugins_path() . '/' . $modelAttributeAdresse);
        }
        $maped = $this->remapAttributes($attributes['attributes'], $this->dataSource->lowerName);
        //$attributeArray[$this->dataSource->lowerName] = $attributes;
        $attributeArray[$this->dataSource->lowerName]['values'] = $maped;
        $attributeArray[$this->dataSource->lowerName]['icon'] = $attributes['icon'];
        foreach ($this->dataSource->relations as $key => $relation) {
            $ex = explode('.', $key);
            $relationName = array_pop($ex);
            $attributesPath = plugins_path() . '/' . $pluginName . '/' . $relationName . '/attributes.yaml';
            $attributes;
            if (file_exists($attributesPath)) {
                $attributes = Yaml::parseFile($attributesPath);
            } else {
                $modelAttributeAdresse = $this->dataSource->attributesConfig;
                $attributes = Yaml::parseFile(plugins_path() . '/' . $modelAttributeAdresse);
            }
            $maped = $this->remapAttributes($attributes['attributes'], $relationName, $this->dataSource->lowerName);
            $attributeArray[$relationName]['values'] = $maped;
            $attributeArray[$relationName]['icon'] = $attributes['icon'];
        }
        trace_log($attributeArray);
        return $attributeArray;
    }

    public function remapAttributes(array $attributes, $relationOrName, $name = null)
    {
        $transformers = \Config::get('waka.utils::transformers');
        $documentType = 'twig';
        if ($this->type == 'word') {
            $documentType = 'word';
        }

        $mapedResult = [];
        foreach ($attributes as $key => $attribute) {
            //trace_log($attribute);
            $type = $attribute['type'] ?? null;
            $label = $attribute['label'] ?? null;

            //Gestion du keyName
            $KeyName;
            if ($name) {
                $KeyName = $name . '.' . $relationOrName . '.' . $key;
            } else {
                $KeyName = $relationOrName . '.' . $key;
            }

            //Application de la transformation
            trace_log("type : " . $type . " | " . $KeyName);
            if ($type) {
                $transformer = $transformers['types'][$type][$documentType] ?? null;
                trace_log($transformer);
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
        trace_log($mapedResult);
        return $mapedResult;

    }

    public function getIMG()
    {
        //trace_log(get_class($this->model));
        $imgs = $this->model->images;

        if (!$imgs) {
            return [];
        }
        $result = [];
        foreach ($imgs as $img) {
            $obj = [
                'code' => $img['code'],
                'width' => $img['width'],
                'height' => $img['height'],
            ];
            array_push($result, $obj);
        }
        return $result;
    }

    public function getFNCOutputs()
    {
        $this->dataSource->getAttributes();
        $fncs = $this->model->model_functions;
        if (!$fncs) {
            return [];
        }
        $result = [];
        // trace_log($modelTest->toArray());
        // trace_log($this->model->toArray());
        foreach ($fncs as $fnc) {
            $code = $fnc['collectionCode'];

            $outputs = $this->dataSource->getFunctionsOutput($fnc['functionCode']);
            //trace_log($outputs);
            if ($outputs) {
                $attributes = $outputs['attributes'] ?? null;
                if ($attributes) {
                    foreach ($attributes as $attributeAdresse) {
                        $attributeArray = Yaml::parseFile(plugins_path() . '/' . $attributeAdresse);
                        //trace_log($attributeArray);
                        //$result = array_merge($attributes, $attributeArray);
                    }
                }
                $images = $outputs['images'] ?? null;
                if ($images) {
                    foreach ($images as $image) {
                        $result[$code][$image . '.path'] = 'IMAGE';
                    }
                }
                $values = $outputs['values'] ?? null;
                if ($values) {
                    foreach ($values as $key => $value) {
                        $result[$code][$key] = $value;
                    }
                }

            }
        }

        return $result;
    }

    public function loadAssets()
    {
        $this->addCss('css/sidebarattributes.css', 'Waka.Utils');
        $this->addJs('js/clipboard.min.js', 'Waka.Utils');
    }

}
