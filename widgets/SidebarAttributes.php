<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;

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

    public function render()
    {
        $this->perepareModel();
        if (!$this->model) {
            return;
        }
        $this->vars['attributesArray'] = $this->dataSource->getDotedValues();
        $this->vars['IMGSArray'] = $this->getIMG();
        $fncArray = $this->getFNCOutputs();
        trace_log($this->type);
        $this->vars['FNCSArray'] = $fncArray;

        if ($this->type == 'word') {
            return $this->makePartial('list_word');
        } else {
            return $this->makePartial('list');
        }

    }

    public function perepareModel()
    {
        $model = $this->controller->formGetModel();
        $this->model = $model;
        if (!$model) {
            return;
        }

        $this->dataSource = $model->data_source;
        return false;
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
                        $dataApi = $modelFinal->first()->toArray();
                        $result[$code] = array_dot($dataApi);
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
                    foreach ($values as $value) {
                        $result[$code][$value] = 'contenu des P';
                    }
                }

            }
        }

        // $modelTest = $this->model->data_source->getTargetModel();
        // foreach ($fncs as $submodels) {
        //     foreach ($submodels as $submodelKey => $submodelValue) {

        //         $dataApi = $modelTest->{$submodelKey}()->with($submodelValue)->first()->toArray();
        //         array_push($result, $dataApi);
        //     }
        // }

        return $result;
    }

    public function loadAssets()
    {
        $this->addCss('css/sidebarattributes.css', 'Waka.Utils');
        $this->addJs('js/clipboard.min.js', 'Waka.Utils');

        //$this->addJs('js/labellist.js', 'Waka.Utils');
    }

}
