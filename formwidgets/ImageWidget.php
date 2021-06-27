<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Waka\Utils\Classes\DataSource;

/**
 * ImageWidget Form Widget
 */
class ImageWidget extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_utils_images_list';

    public $jsonValues;
    public $imageWidget;

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
        return $this->makePartial('imagewidget');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {

        $noImage = true;
        $ds = new DataSource($this->model->data_source);
        //trace_log($ds->name);
        $ds->instanciateModel(); // instancie l'exemple
        $options = $ds->wimages->getAllPicturesKey();
        if ($options) {
            $noImage = false;
        }
        //$this->vars['options'] = $options;
        $this->vars['noImage'] = $noImage;
        $this->vars['id'] = $this->getId();
        $this->vars['name'] = $this->getFieldName();
        $this->vars['values'] = $this->getLoadValue() ?? [];
        $this->vars['images'] = $ds->wimages->getAllPicturesKey();
        $selectedImage = $this->vars['selectedImage'] = $this->getLoadValue()['selector'] ?? null;
        $imageType = null;
        if($selectedImage) {
            $pictureData = $ds->wimages->getOnePictureKey($selectedImage);
            $imageType = $pictureData['type'] ?? null;
        }
        if($imageType == 'file') {
            $this->vars['cropOptions'] = \Config::get('waka.utils::image.baseCrop');
        } elseif($imageType) {
            $this->vars['cropOptions'] = \Config::get('waka.cloudis::ImageOptions.crop.options');
            $this->vars['gravityOptions'] = \Config::get('waka.cloudis::ImageOptions.gravity.options');
        } 
        $this->vars['imageType'] = $imageType;
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        trace_log($value);
        return $value;
    }

    public function onSelectImages()
    {
        $formFieldValue = post($this->formField->getName());
        $codeImage = trim($formFieldValue['selector']);
        //trace_log($codeImage);
        $ds = new DataSource($this->model->data_source);
        $ds->instanciateModel(); // instancie l'exemple
        $pictureData = $ds->wimages->getOnePictureKey($codeImage);
        $imageType = $pictureData['type'] ?? null;
        $this->vars['id'] = $this->getId();
        $this->vars['name'] = $this->getFieldName();
        $this->vars['value'] = $this->getLoadValue() ?? [];
        if($imageType == 'file') {
            $this->vars['cropOptions'] = \Config::get('waka.utils::image.baseCrop');
            return [
                '#image_option' => $this->makePartial('options_classique'),
            ]; 
        } else {
            $this->vars['cropOptions'] = \Config::get('waka.cloudis::ImageOptions.crop.options');
            $this->vars['gravityOptions'] = \Config::get('waka.cloudis::ImageOptions.gravity.options');
            return [
                '#image_option' => $this->makePartial('options_cloudi'),
            ]; 

        } 
    }
}
