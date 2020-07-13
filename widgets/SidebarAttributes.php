<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;
use \October\Rain\Support\Collection;

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

    public function init()
    {
        $this->fillFromConfig([
            'model',
            'type',
            'text_info',
            'separate_fields',
            'hidden_fields',
        ]);
    }

    public function render()
    {
        $this->dataSource = $this->model->data_source;

        $this->vars['text_info'] = $this->text_info;
        $this->vars['attributesArray'] = $this->cleanModelValues($this->dataSource->getValues());
        $this->vars['hidden_fields'] = $this->hidden_fields;
        $this->vars['IMGSArray'] = $this->getIMG();
        $fncArray = $this->getFNCOutputs();
        $this->vars['FNCSArray'] = $fncArray;

        if ($this->type == 'word') {
            return $this->makePartial('list_word');
        } else {
            return $this->makePartial('list');
        }

    }

    public function cleanModelValues($array)
    {
        $attributesCollection = new Collection($array);
        //trace_log($attributesCollection->toArray());

        $arrays = [];
        $baseModelName = snake_case($this->model->data_source->model);

        if ($this->separate_fields) {
            foreach ($this->separate_fields as $field) {
                $newArray = $attributesCollection->pull($field);

                $tempObj = [
                    $baseModelName => [
                        $field => $newArray,
                    ],

                ];
                $arrays[$field] = array_dot($tempObj);
            }
        }
        $arrays = array_reverse($arrays);

        $baseArray = [
            $baseModelName => $attributesCollection->toArray(),
        ];

        $arrays['base'] = array_dot($baseArray);

        $arrays = array_reverse($arrays);
        // $arrays = new Collection($arrays);
        // $arrays = $arrays->reject(function ($item) {
        //     trace_log($item);
        //     return false;
        // });

        return $arrays;
    }

    public function getIMG()
    {
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
        $fncs = $this->model->model_functions;
        if (!$fncs) {
            return [];
        }
        $result = [];
        $modelTest = $this->model->data_source->getTargetModel();
        foreach ($fncs as $fnc) {
            $code = $fnc['collectionCode'];
            $outputs = $this->model->data_source->getFunctionsOutput($fnc['functionCode']);
            if ($outputs) {
                $relations = $outputs['relations'] ?? null;
                if ($relations) {
                    foreach ($relations as $submodelKey => $submodelValue) {
                        $modelFinal = $this->getStringModelRelation($modelTest, $submodelKey);
                        $dataApi = $modelFinal->first();
                        if ($dataApi) {
                            $result[$code] = array_dot($dataApi->toArray());
                        }

                    }
                }
                $models = $outputs['models'] ?? null;
                if ($models) {
                    foreach ($models as $model) {
                        $result[$code] = array_dot($model);
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
