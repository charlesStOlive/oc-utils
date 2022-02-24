<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * range Form Widget
 */
class Range extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_utils_range';

    public $min = 0;
    public $max = 100;
    public $default = 50;
    public $step = 1;
    public $label;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'min',
            'max',
            'default',
            'label',
            'step'
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('range');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        
        $this->vars['min'] = $this->min;
        $this->vars['max'] = $this->max;
        if($this->default && !$this->getLoadValue()) {
            $this->vars['value'] = $this->default;
        } else {
            $this->vars['value'] = $this->getLoadValue();
        }
        $this->vars['model'] = $this->model;
        $this->vars['step'] = floatval($this->step);
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        // $this->addCss('css/range.css', 'waka.utils');
        // $this->addJs('js/range.js', 'waka.utils');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return $value;
    }
}
