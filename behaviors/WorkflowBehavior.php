<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;

class WorkflowBehavior extends ControllerBehavior
{
    public $controller;
    public $workflowWidget;
    public $model;
    public $config;
    /**
     * @inheritDoc
     */
    protected $requiredProperties = ['workflowConfig'];

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     */
    protected $requiredConfig = ['places'];

    /**
     * @inheritDoc
     */

    //protected $workflowWidget;

    public function __construct($controller)
    {
        parent::__construct($controller);
        $this->workflowWidget = new \Waka\Utils\Widgets\Workflow($controller);
        $this->workflowWidget->alias = 'workflow';
        $this->controller = $controller;
        $this->workflowWidget->model = $controller->formGetModel();
        $this->workflowWidget->config = $this->config = $this->makeConfig($controller->workflowConfig, $this->requiredConfig);
        $this->workflowWidget->bindToController();
    }

    public function formBeforeSave($model)
    {
        if (post('change_state') != '') {
            $model->change_state = post('change_state');
        }
    }

}
