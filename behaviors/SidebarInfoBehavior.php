<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;
use \System\Traits\ConfigMaker;
use \Backend\Traits\WidgetMaker;



class SidebarInfoBehavior extends ControllerBehavior
{
    /**
     * @inheritDoc
     */
    protected $requiredProperties = ['sidebarInfoConfig'];

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     */
    protected $requiredConfig = ['fields'];

    /**
     * @var Model Import model
     */
    public $model;
    public $sidebarInfo;

	//protected $exportExcelWidget;

	public function __construct($controller)
    {
        parent::__construct($controller);
        /*
         * Build configuration
         */
        $this->sidebarInfo = new \Waka\Utils\Widgets\SidebarInfo($controller);
        $this->sidebarInfo->alias = 'info';
        $this->sidebarInfo->model = $controller->formGetModel();
        $this->sidebarInfo->config = $this->makeConfig($controller->sidebarInfoConfig, $this->requiredConfig);
        $this->sidebarInfo->bindToController();
    }

    
}