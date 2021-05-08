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
        return $this->makePartial('$/waka/utils/behaviors/btnsbehavior/_popup.htm');
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

        //POUR L INSTANT JE DESACTIVE LES LISTES TROP DANGEREUX
        // $lists = $this->controller->makeLists();
        // $widget = $lists[0] ?? reset($lists);
        // $query = $widget->prepareQuery();
        // $results = $query->get();

        $checkedIds = post('checked');

        trace_log($checkedIds);

        $countCheck = null;
        if (is_countable($checkedIds)) {
            $countCheck = count($checkedIds);
        }
        //
        //POUR L INSTANT JE DESACTIVE LES LISTES TROP DANGEREUX
        //Session::put('lot.listId', $results->lists('id'));
        Session::put('lot.checkedIds', $checkedIds);
        //
        $modelClass = post('modelClass');
        $this->vars['modelClass'] = $modelClass;
        //$this->vars['filtered'] = $query->count();
        $this->vars['countCheck'] = $countCheck;

        return $this->makePartial('$/waka/utils/behaviors/btnsbehavior/_popup_export.htm');
    }

    /**
     * ************************************Traitement par lot**********************************
     */
    public function onExecuteLotFnc()
    {
        $options = $this->btnsWidget->config->tool_bar['config_lot']['btns']['lot_fnc']['fncNames'];
        $this->vars['options'] = $options;
        return ['#popupActionContent' => $this->makePartial('$/waka/utils/behaviors/btnsbehavior/_lot.htm')];
    }

    public function onExecuteLotFncValidation()
    {
        $errors = $this->CheckIndexValidation(\Input::all());
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        $lotType = post('lotType');
        $fncName = post('fncName');
        //POUR L INSTANT JE DESACTIVE LES LISTES TROP DANGEREUX
        // $listIds = null;
        // if ($lotType == 'filtered') {
        //     $listIds = Session::get('lot.listId');
        // } elseif ($lotType == 'checked') {
        //     $listIds = Session::get('lot.checkedIds');
        // }
        // // Session::forget('lot.listId');
        // // Session::forget('lot.checkedIds');
        //
        //trace_log($this->btnsWidget->config->modelClass);
        $listIds = Session::get('lot.checkedIds');
        $datas = [
            'listIds' => $listIds,
            'modelClass' => $this->btnsWidget->config->modelClass,
            'fncName' => $fncName,
        ];
        try {
            $job = new \Waka\Utils\Jobs\ExecuteFnc($datas);
            $jobManager = \App::make('Waka\Wakajob\Classes\JobManager');
            $jobManager->dispatch($job, "Execution de fonctions par lot");
            $this->vars['jobId'] = $job->jobId;
        } catch (Exception $ex) {
                $this->controller->handleError($ex);
        }
        return ['#popupActionContent' => $this->makePartial('$/waka/wakajob/controllers/jobs/_confirm.htm')];
    }

    

    public function CheckIndexValidation($inputs)
    {
        $rules = [
            'fncName' => 'required',
        ];

        $validator = \Validator::make($inputs, $rules);

        if ($validator->fails()) {
            return $validator->messages()->first();
        } else {
            return false;
        }
    }

}
