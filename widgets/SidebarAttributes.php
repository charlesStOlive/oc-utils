<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;
use Waka\Utils\Classes\DataSource;
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
        $this->dataSource = new DataSource($this->model->data_source_id, 'id');

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

        $arrays = [];
        $baseModelName = snake_case($this->dataSource->name);

        if ($this->separate_fields) {
            foreach ($this->separate_fields as $field) {

                //On enlève de la collection et on range dans new Array la colone trouvé dans separateField
                $newArray = $attributesCollection->pull($field);
                // trace_log("new array");
                // trace_log($newArray);

                $tempObj = [
                    $baseModelName => [
                        $field => $newArray,
                    ],

                ];

                $arrays[$field] = array_dot($tempObj);
                $arrays[$field] = $this->cleanField($arrays[$field], $this->hidden_fields);

            }
        }

        $arrays = array_reverse($arrays);

        $baseArray = [
            $baseModelName => $attributesCollection->toArray(),
        ];

        $arrays['base'] = array_dot($baseArray);
        $arrays['base'] = $this->cleanField($arrays['base'], $this->hidden_fields);

        //trace_log($this->changeName());

        $arrays = array_reverse($arrays);

        return $arrays;
    }

    public function changeName()
    {
        //trace_log($this->lang_fields);

    }
    public function cleanField($rows, $hiddeArrayFields)
    {
        foreach ($rows as $key => $row) {
            //trace_log("analyse du row : " . $key);
            foreach ($hiddeArrayFields as $hiddenField) {
                if (str_contains($key, $hiddenField)) {
                    //trace_log("remove : " . $key);
                    unset($rows[$key]);
                }
            }

        }
        // trace_log($rows);
        // trace_log($this->hidden_fields);

        return $rows;

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
        //$modelTest = $this->model->data_source->getTargetModel();
        // trace_log($modelTest->toArray());
        // trace_log($this->model->toArray());
        foreach ($fncs as $fnc) {
            $code = $fnc['collectionCode'];

            $outputs = $this->dataSource->getFunctionsOutput($fnc['functionCode']);
            //trace_log($outputs);
            if ($outputs) {

                $relations = $outputs['relations'] ?? null;

                if ($relations) {
                    foreach ($relations as $submodelKey => $submodelValue) {
                        // trace_log("----" . $code . "----");
                        // trace_log($submodelKey);
                        // trace_log($submodelValue);
                        $modelFinal = $this->getStringRequestRelation($this->dataSource->model, $submodelKey);

                        // //trace_log($modelFinal->with($submodelValue)->get()->toArray());
                        $dataApi = $modelFinal->with($submodelValue)->get()->first();

                        if ($dataApi) {
                            $result[$code] = array_dot($dataApi->toArray());
                            $result[$code] = $this->cleanField($result[$code], $this->hidden_fields);
                        }

                    }
                }
                $models = $outputs['models'] ?? null;
                if ($models) {
                    foreach ($models as $model) {
                        $result[$code] = array_dot($model);
                        $result[$code] = $this->cleanField($result[$code], $this->hidden_fields);
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
