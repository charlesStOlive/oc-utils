<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * attributs Form Widget
 */
class Attributs extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_utils_attributs';


   

    public $model;
    public $dataSource;
    public $mode = 'twig';
    public $text_info;
    public $valueArray;
    public $lang_fields;

    public function init()
    {
        $this->fillFromConfig([
            'model',
            'mode',
            'text_info',
            'lang_fields',
        ]);
    }

    /**
     * Prepares the form widget view data
     */
    public function render()
    {
        $attributes = new \Waka\utils\Classes\Wattributes($this->model, $this->mode);

        $this->vars['text_info'] = $this->text_info;
        //trace_log('attributes->getWAttributes()');
        //trace_log($attributes->getWAttributes());
        $this->vars['attributesArray'] = $attributes->getWAttributes();
        $fncArray = $attributes->getFncsOutputs($this->model->rule_fncs);
        $askArray = $attributes->getAsks($this->model->rule_asks);
        $this->vars['FNCSArray'] = $fncArray;
        $this->vars['ASKSArray'] = $askArray;
        //trace_log($this->mode);
        if ($this->mode == 'word') {
            return $this->makePartial('list');
        } else {
            return $this->makePartial('list');
        }
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        
        //$this->addJs('js/attributs.js');
        // $this->addCss(); INUTILE EST GERE DANS LE WAKA.LESS de WCONFIG

    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return \Backend\Classes\FormField::NO_SAVE_DATA;
    }
}
