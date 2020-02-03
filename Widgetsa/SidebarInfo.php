<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;
use Yaml;
use ApplicationException;
use lang;

use Waka\Utils\Classes\ParseFields;

class SidebarInfo extends WidgetBase
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'info';

    public $config;
    public $model;
    public $fields;

    public function render()
    {
        $this->perepareVars();
        $this->vars['fields'] = $this->fields;
        return $this->makePartial('sidebar_info');
    }

    public function perepareVars() {
        $model = $this->controller->formGetModel();
        $returnFields = new ParseFields();
        $this->fields = $returnFields->parseFields($model, $this->config->fields);
    }

    public function loadAssets()
    {
        $this->addCss('css/sidebarinfo.css', 'Waka.Utils');
        //$this->addJs('js/labellist.js', 'Waka.Utils');
    }

    
}












