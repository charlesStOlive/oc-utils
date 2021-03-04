<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;
use File;

class SideBarAttributesBehavior extends ControllerBehavior
{
    use \Backend\Traits\FormModelSaver;
    /**
     * @inheritDoc
     */
    protected $requiredProperties = ['sidebarAttributesConfig'];
    protected $optionalProperties = '$/wcli/wconfig/config/attributes.yaml';

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
        $tempConfig = $this->mergeConfig($controller->sidebarAttributesConfig, $this->optionalProperties);
        $this->config = $this->makeConfig($tempConfig, $this->requiredConfig);
    }

    public function initForm($model, $context = null)
    {
        $tempConfig = $this->mergeConfig($this->controller->sidebarAttributesConfig, $this->optionalProperties);
        $this->config = $this->makeConfig($tempConfig, $this->requiredConfig);
        //$config = $this->makeConfig($this->controller->sidebarAttributesConfig, $this->requiredConfig);
        $this->config->model = $model;
        $this->config->arrayName = 'attributes';
        $this->sidebarAttributes = $this->makeWidget('Waka\Utils\Widgets\SidebarAttributes', $this->config);
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
