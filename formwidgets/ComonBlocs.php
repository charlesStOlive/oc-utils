<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * comonBlocs Form Widget
 */
class ComonBlocs extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_utils_comon_blocs';

    public $blocClass;
    public $twigName;
    public $dataName = 'ds';

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'blocClass',
            'twigName',
            'dataName',
        ]);
    }

    public function render()
    {
        $blocs = $this->getBlocs();
        $this->vars['blocs'] = $blocs;
        return $this->makePartial('comonblocs');
    }

    public function getBlocs()
    {
        $name = 'ds';
        $blocs = $this->blocClass::get();
        return $blocs->map(function ($item, $key) {
            $item['code'] = "{{".$this->twigName."('" . $item['slug'] . "'," . $this->dataName . ")}}";
            return $item;
        });

    }


    // /**
    //  * @inheritDoc INUTILE EST GERE DANS LE WAKA.LESS de WCONFIG
    //  */
    // public function loadAssets()
    // {
    //     $this->addCss('css/comonblocs.css');
    // }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return \Backend\Classes\FormField::NO_SAVE_DATA;
    }
}
