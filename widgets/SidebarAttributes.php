<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;

class SidebarAttributes extends WidgetBase
{
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
        $this->vars['attributesArray'] = $this->dataSource->getDotedValues();
        $this->vars['IMGSArray'] = $this->getIMG();
        $fncArray = $this->getFNCOutputs();
        trace_log($this->type);
        $this->vars['FNCSArray'] = $fncArray;
        return $this->makePartial('list');
    }

    public function perepareModel()
    {
        $model = $this->controller->formGetModel();
        $this->model = $model;

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

                $values = $outputs['values'];
                foreach ($values as $submodelKey => $submodelValue) {
                    $dataApi = $modelTest->{$submodelKey}()->with($submodelValue)->first()->toArray();
                    $result[$code] = array_dot($dataApi);
                }
                $images = $outputs['images'];
                foreach ($images as $image) {
                    $result[$code][$image . '.path'] = 'IMAGE';
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
        //$this->addJs('js/labellist.js', 'Waka.Utils');
    }

}
