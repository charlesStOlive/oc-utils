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
    public $mode;
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
        if($this->model->no_ds) {
            return $this->makePartial('empty');
        }
        $this->dataSource = \DataSources::find($this->model->data_source);
        $attributes = new \Waka\utils\Classes\Wattributes($this->model, $this->mode);

        $this->vars['text_info'] = $this->text_info;
        $this->vars['attributesArray'] = $attributes->getAttributes();
        $fncArray = $attributes->getFncsOutputs($this->model->rule_fncs);
        $this->vars['FNCSArray'] = $fncArray;
        //trace_log($this->mode);
        if ($this->mode == 'word') {
            return $this->makePartial('list_word');
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
