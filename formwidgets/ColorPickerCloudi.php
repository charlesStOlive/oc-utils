<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;
use ColorPalette;

/**
 * Color picker
 * Renders a color picker field.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class ColorPickerCloudi extends FormWidgetBase
{
    //
    // Configurable properties
    //

    /**
     * @var array Default available colors
     */
    public $availableColors = [
        '#1abc9c', '#16a085',
        '#2ecc71', '#27ae60',
        '#3498db', '#2980b9',
        '#9b59b6', '#8e44ad',
        '#34495e', '#2b3e50',
        '#f1c40f', '#f39c12',
        '#e67e22', '#d35400',
        '#e74c3c', '#c0392b',
        '#ecf0f1', '#bdc3c7',
        '#95a5a6', '#7f8c8d',
    ];

    /**
     * @var bool Allow empty value
     */
    public $allowEmpty = false;

    /**
     * @var bool Show opacity slider
     */
    public $showAlpha = false;

    //
    // Object properties
    //

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'colorpicker';

    /**
     * @inheritDoc
     */
    protected $colorsFrom = 'logo_c';

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'availableColors',
            'allowEmpty',
            'showAlpha',
            'colorsFrom',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('colorpickercloudi');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['name'] = $this->getFieldName();
        $this->vars['value'] = $value = $this->getLoadValue();
        $this->vars['availableColors'] = $availableColors = $this->getAvailableColors();
        $this->vars['allowEmpty'] = $this->allowEmpty;
        $this->vars['showAlpha'] = $this->showAlpha;
        $this->vars['isCustomColor'] = !in_array($value, $availableColors);
    }

    /**
     * Gets the appropriate list of colors.
     *
     * @return array
     */
    protected function getAvailableColors()
    {
        //trace_log("get avaiable colors");
        //trace_log($this->colorsFrom);
        $availableColors = $this->availableColors;
        $cloudiObject = $this->model->{$this->colorsFrom};
        //trace_log($cloudiObject);

        $path = null;
        if ($cloudiObject) {
            //trace_log('il y a un cloudiObject');
            $path = $cloudiObject->getUrl();
            trace_log($path);
        }
        if ($path) {
            try {
            $availableColors = ColorPalette::getPalette($path, 6, 10);
            } catch(\RuntimeException $e) {
                $availableColors = [];
            }
        }
        return $availableColors;
    }

    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addCss('vendor/spectrum/spectrum.css', 'Waka.Utils');
        $this->addJs('vendor/spectrum/spectrum.js', 'Waka.Utils');
        $this->addCss('css/colorpicker.css', 'Waka.Utils');
        $this->addJs('js/colorpicker.js', 'Waka.Utils');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return strlen($value) ? $value : null;
    }
}
