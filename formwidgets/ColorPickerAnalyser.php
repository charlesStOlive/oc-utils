<?php namespace Waka\Utils\FormWidgets;

use Lang;
use Backend\Classes\FormWidgetBase;
use ApplicationException;
use ColorPalette;

/**
 * Color picker
 * Renders a color picker field.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class ColorPickerAnalyser extends FormWidgetBase
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
        return $this->makePartial('colorpickeranalyser');
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
        $availableColors = $this->availableColors;
        $path;
        $path = $this->model[$this->colorsFrom];
        $file= null;
        $file = $this->model->{$this->colorsFrom}()->withDeferred($this->sessionKey);
        $model = $this->model;
        //trace_log($file);
        if(!$this->fromMedia) {
            if($file->first()) {
                $path = $file->first()->getLocalPath();
                // trace_log("deffered file --------------------------- : ");
                // trace_log($file->get()->toArray());
                // trace_log("normal ----------------------------------------");
                // trace_log($this->model->{$this->colorsFrom}()->get()->toArray());
                return $availableColors = ColorPalette::getPalette($path,6,10); 
                
            }
        }
        elseif($path) {
        $path = storage_path('app/media/'.$this->model[$this->colorsFrom]);
        } if($path) {
            return $availableColors = ColorPalette::getPalette($path,6,10); 
        }
        elseif (is_array($availableColors)) {
            return $availableColors;
        }
        elseif (is_string($availableColors) && !empty($availableColors)) {
            if ($this->model->methodExists($availableColors)) {
                return $this->availableColors = $this->model->{$availableColors}(
                    $this->formField->fieldName,
                    $this->formField->value,
                    $this->formField->config
                );
            } else {
                throw new ApplicationException(Lang::get('backend::lang.field.colors_method_not_exists', [
                    'model'  => get_class($this->model),
                    'method' => $availableColors,
                    'field'  => $this->formField->fieldName
                ]));
            }
        }
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
