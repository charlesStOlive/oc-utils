<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;

class SideBarUpdate extends ControllerBehavior
{
    protected $sideBarPopupWidget;
    protected $sideBarConfig;
    protected $requiredProperties = ['sideBarUpdateConfig'];
    protected $requiredConfig = ['modelClass', 'form'];


    public function __construct($controller)
    {
        parent::__construct($controller);
        $this->sideBarConfig = $this->makeConfig($controller->sideBarUpdateConfig, $this->requiredConfig);
        $this->sideBarPopupWidget = $this->createSideBarPopupWidget();
    }

    /**
     ******************** LOAD DES POPUPS et du test******************************
     */

    public function onLoadSideBarUpdateForm()
    {
        $modelId = post('modelId');
        $this->vars['sideBarPopupWidget'] = $this->sideBarPopupWidget =  $this->createSideBarPopupWidget($modelId);
        $this->vars['title'] = $this->sideBarConfig->title;
        $this->vars['modelId'] = $modelId;
        return $this->makePartial('$/waka/utils/behaviors/sidebarupdate/_popup.htm');
    }

    public function onSideBarUpdateValidation() {
        $modelId = post('modelId');
        $model =  $this->sideBarConfig->modelClass::find($modelId);
        $data = $this->sideBarPopupWidget->getSaveData();
        $model->fill($data);
        $model->save();
        return \Redirect::refresh();

    }


    /**
     * Cette fonction est utilisé lors du test depuis le controller wakamail.
    */

    public function createSideBarPopupWidget($modelId = null)
    {
        $config = $this->makeConfig($this->sideBarConfig->form);
        //Suppreion des noUpdate
        $configFields = $config->fields;
        foreach($configFields as $key=>$field) {
            if($field['noUpdate'] ?? false) {
                unset($configFields[$key]);
            }
        }
        $config->fields = $configFields;
        $config->alias = 'sideBarUpdateformWidget';
        $config->arrayName = 'sideBarUpdate_array';
        if($modelId ) {
            $config->model =  $this->sideBarConfig->modelClass::find($modelId);
        } else {
            $config->model = new  $this->sideBarConfig->modelClass;
        }
        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->bindToController();
        return $widget;
    }
}
