<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;

class SideBarAttributesBehavior extends ControllerBehavior
{
    use \Backend\Traits\FormModelSaver;
    /**
     * @inheritDoc
     */
    protected $requiredProperties = ['sidebarInfoConfig'];

    /**
     * @var array Visible actions in context of the controller
     */
    protected $actions = ['update'];

    protected $controller;

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     * - modelClass: Class name for the model
     */
    protected $requiredConfig = ['modelClass'];

    public $sidebarAttributes;

    public $model;

    // /**
    //  * @var Model Import model
    //  */
    // public $model;
    // public $sidebarInfo;

    //protected $exportExcelWidget;

    public function __construct($controller)
    {
        parent::__construct($controller);
        //trace_log("construct sideBarAttributes");
        $this->config = $this->makeConfig($controller->sidebarInfoConfig, $this->requiredConfig);
    }

    public function initForm($model, $context = null)
    {
        $config = $this->makeConfig($this->controller->sidebarInfoConfig);
        $config->model = $model;
        $config->arrayName = 'attributes';
        $this->sidebarAttributes = $this->makeWidget('Waka\Utils\Widgets\SidebarAttributes', $config);
        $this->sidebarAttributes->bindToController();
    }

    public function attributesRender($idFromParam = null)
    {
        $model;
        if (!$idFromParam) {
            $model = $this->controller->formGetModel();
        } else {
            $modelClass = new $this->config->modelClass;
            $model = $modelClass::find($idFromParam);
        }
        //
        $this->initForm($model);

        if (!$this->sidebarAttributes) {
            throw new \ApplicationException("les attributs ne sont pas ready");
        }

        return $this->sidebarAttributes->render();
    }

}
