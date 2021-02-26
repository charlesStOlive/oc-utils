<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;
use Session;

class BtnsBehavior extends ControllerBehavior
{
    /**
     * @inheritDoc
     */
    protected $requiredProperties = ['btnsConfig'];

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     */
    protected $requiredConfig = ['modelClass'];

    public $config;
    public $btnsWidget;
    public $workflowWidget;

    public function __construct($controller)
    {
        parent::__construct($controller);

        $this->btnsWidget = new \Waka\Utils\Widgets\Btns($controller);
        $this->btnsWidget->alias = 'btnsWidget';
        $this->controller = $controller;
        $this->btnsWidget->model = $controller->formGetModel();
        $this->btnsWidget->config = $this->makeConfig($controller->btnsConfig, $this->requiredConfig);
        $this->btnsWidget->bindToController();
    }

    public function onLoadActionPopup()
    {
        $this->vars['modelClass'] = post('modelClass');
        $this->vars['modelId'] = post('modelId');
        return $this->makePartial('$/waka/utils/behaviors/btnsBehavior/_popup.htm');
    }

    public function formBeforeSave($model)
    {
        if (post('change_state') != '') {
            $model->change_state = post('change_state');
        }
    }

    /**
     * Cette fonction est appelé par le bouton lot dans le BTNBehavior
     */

    public function onExportLotPopupForm()
    {
        //liste des requêtes filtrées
        $lists = $this->controller->makeLists();
        $widget = $lists[0] ?? reset($lists);
        $query = $widget->prepareQuery();
        $results = $query->get();

        $checkedIds = post('checked');

        $countCheck = null;
        if (is_countable($checkedIds)) {
            $countCheck = count($checkedIds);
        }
        //
        Session::put('lot.listId', $results->lists('id'));
        Session::put('lot.checkedIds', $checkedIds);
        //
        $modelClass = post('modelClass');
        $this->vars['modelClass'] = $modelClass;
        $this->vars['filtered'] = $query->count();
        $this->vars['countCheck'] = $countCheck;

        return $this->makePartial('$/waka/utils/behaviors/btnsbehavior/_popup_export.htm');
    }
}
