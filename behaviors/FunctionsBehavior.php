<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;

class FunctionsBehavior extends ControllerBehavior
{
    /**
     * @inheritDoc
     */
    //protected $requiredProperties = ['functionsConfig'];

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     */
    //protected $requiredConfig = ['model'];

    /**
     * @var Model Import model
     */
    public $model;
    public $functionsList;

    //protected $exportExcelWidget;

    public function __construct($controller)
    {
        parent::__construct($controller);
        /*
         * Build configuration
         */
        $this->functionsList = new \Waka\Utils\Widgets\FunctionsList($controller);
        $this->functionsList->alias = 'functionsList';
        $this->functionsList->model = $controller->formGetModel();
        //$this->functionsList->config = $this->makeConfig($controller->sidebarInfoConfig, $this->requiredConfig);
        $this->functionsList->bindToController();
    }

}
