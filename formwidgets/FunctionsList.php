<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * FunctionsList Form Widget
 */
class FunctionsList extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_utils_functions_list';

    /**
     * @inheritDoc
     */
    public function init()
    {
    }

    public $functionClass;

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('functionslist');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->vars['functionClass'] = $this->model->data_source->getFunctionClass();
        $this->vars['name'] = $this->formField->getName();
        $this->vars['values'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;

    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->addCss('css/functionslist.css', 'Waka.Utils');
        $this->addJs('js/functionslist.js', 'Waka.Utils');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return $value;
    }

    public function onShowFunctions()
    {
        $this->vars['functionList'] = $this->functionClass->getFunctionsList();
        return $this->makePartial('popup');

    }

    public function onChooseFunction()
    {
        $attributes = $this->functionClass->getFunctionAttribute();
        if ($attributes) {
            $this->vars['attributes'] = $attributes;
            return [
                '#functionAttribute' => $this->makePartial('attributes'),
            ];
        }

    }

    public function createFormWidget()
    {

    }
}
