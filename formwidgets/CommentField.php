<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * CommentField Form Widget
 */
class CommentField extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_utils_comment_field';

    public $type = "info";

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'type',
            'valueFrom',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('commentfield');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['comment'] = $this->model[$this->valueFrom];
        $this->vars['type'] = $this->type;
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->addCss('css/commentfield.css', 'Waka.Utils');
        $this->addJs('js/commentfield.js', 'Waka.Utils');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return \Backend\Classes\FormField::NO_SAVE_DATA;
    }
}
