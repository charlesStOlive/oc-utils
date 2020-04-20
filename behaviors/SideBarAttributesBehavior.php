<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;

class SideBarAttributesBehavior extends ControllerBehavior
{
    // /**
    //  * @inheritDoc
    //  */
    // protected $requiredProperties = ['sidebarInfoConfig'];

    // /**
    //  * @var array Configuration values that must exist when applying the primary config file.
    //  */
    // protected $requiredConfig = ['fields'];

    // /**
    //  * @var Model Import model
    //  */
    // public $model;
    // public $sidebarInfo;

    //protected $exportExcelWidget;

    public function __construct($controller)
    {
        parent::__construct($controller);
        /*
         * Build configuration
         */
        trace_log('controller');
        $this->sidebarAttributes = new \Waka\Utils\Widgets\SidebarAttributes($controller);
        $this->sidebarAttributes->alias = 'SideBarAttributes';
        $this->sidebarAttributes->bindToController();
    }

}
