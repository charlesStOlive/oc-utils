<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Waka\Utils\Classes\DataSource;

/**
 * ImagesList Form Widget
 */
class ImagesList extends FormWidgetBase
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
        $this->imageWidget = $this->createFormWidget();
    }

    public $functionClass;

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('imageslist');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {

        $noImage = true;
        $ds = new DataSource($this->model->data_source);
        //trace_log($ds->name);
        $imagesList = [];
        if($this->model->test_id) {
            $ds->instanciateQuery($this->model->test_id); // instancie l'exemple
            $imagesList = $ds->wimages->getAllPicturesKey();
            $noImage = false;
        } 
        $this->vars['noImage'] = $noImage;
        $this->vars['name'] = $this->formField->getName();
        $this->vars['values'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
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
        return \Backend\Classes\FormField::NO_SAVE_DATA;
    }

    public function onShowImages()
    {
        $ds = new DataSource($this->model->data_source);
        $ds->instanciateQuery();

        //liste des images de la classe depuis le datasource
        $this->imageWidget->getField('source')->options = $ds->wimages->getAllPicturesKey();
        $this->vars['imageWidget'] = $this->imageWidget;
        return $this->makePartial('popup');
    }

    public function onSelectImages()
    {
        $ds = new DataSource($this->model->data_source);
        $ds->instanciateQuery();
    }

    public function onCreateImageValidation()
    {
        $ds = new DataSource($this->model->data_source);
        $ds->instanciateQuery();
        //mis d'en une collection des données existantes
        $data = [];
        $modelImagesValues = $this->getLoadValue();
        if ($modelImagesValues && count($modelImagesValues)) {
            $datas = new \Winter\Storm\Support\Collection($modelImagesValues);
        } else {
            $datas = new \Winter\Storm\Support\Collection();
        }
        //preparatio de l'array a ajouter
        $imageOptionsArray = post('imageList_array');

        $imageInfo = $ds->wimages->getOnePictureKey($imageOptionsArray['source']);
        $imageOptionsArray = array_merge($imageOptionsArray, $imageInfo);

        $datas->push($imageOptionsArray);

        //enregistrement du model
        $field = $this->fieldName;
        $this->model[$field] = $datas;
        $this->model->save();

        $this->updateSideBarreAttribute();

        //rafraichissement de la liste
        return [
            '#listimagesoptions' => $this->makePartial('listimagesoptions', ['values' => $datas]),
        ];
    }
    public function onUpdateImage()
    {
        $ds = new DataSource($this->model->data_source);
        $ds->instanciateQuery();

        $code = post('code');
        $source = post('source');

        $modelValues = $this->getLoadValue();
        // trace_log($modelValues);
        $datas = new \Winter\Storm\Support\Collection($modelValues);
        $data = $datas->where('code', $code)->first();

        $this->imageWidget = $this->createFormWidget();
        $this->imageWidget->getField('source')->options = $ds->wimages->getAllPicturesKey();
        // $this->imageWidget->getField('crop')->options = \Config::get('waka.cloudis::ImageOptions.crop.options');
        // $this->imageWidget->getField('gravity')->options = \Config::get('waka.cloudis::ImageOptions.gravity.options');
        $this->imageWidget->getField('code')->value = $data['code'];
        $this->imageWidget->getField('source')->value = $data['source'] ?? null;
        $this->imageWidget->getField('width')->value = $data['width'] ?? null;
        $this->imageWidget->getField('height')->value = $data['height'] ?? null;
        $this->imageWidget->getField('crop')->value = $data['crop'] ?? null;
        $this->imageWidget->getField('gravity')->value = $data['gravity'] ?? null;
        $this->vars['imageWidget'] = $this->imageWidget;
        $this->vars['oldCode'] = $code;
        $this->vars['oldSource'] = $source;

        return $this->makePartial('popup_update');
    }
    public function onDeleteImage()
    {

        $code = post('code');
        $datas = $this->getLoadValue();

        $updatedDatas = [];
        foreach ($datas as $key => $data) {
            if ($data['code'] != $code) {
                $updatedDatas[$key] = $data;
            }
        }

        //enregistrement du model
        $field = $this->fieldName;
        $this->model[$field] = $updatedDatas;
        $this->model->save();

        $this->updateSideBarreAttribute();

        return [
            '#listimagesoptions' => $this->makePartial('listimagesoptions', ['values' => $updatedDatas]),
        ];
    }
    public function onUpdateImageValidation()
    {
        $ds = new DataSource($this->model->data_source);
        $ds->instanciateQuery();
        //On range collection code hidden das oldCollectionCode au cas ou le user change le collectionCode qui est notre clé
        $oldCode = post('oldCode');
        //mis d'en une collection des données existantes
        $datas = $this->getLoadValue();

        //trace_log($oldCode);

        //preparatio de l'array a ajouter
        $imageOptionsArray = post('imageList_array');
        $imageInfo = $ds->wimages->getOnePictureKey($imageOptionsArray['source']);
        $imageOptionsArray = array_merge($imageOptionsArray, $imageInfo);
        //trace_log($imageOptionsArray);

        foreach ($datas as $key => $data) {
            if ($data['code'] == $oldCode) {
                $datas[$key] = $imageOptionsArray;
            }
        }

        //enregistrement du model
        $field = $this->fieldName;
        $this->model[$field] = $datas;
        $this->model->save();

        $this->updateSideBarreAttribute();

        //rafraichissement de la liste
        return [
            '#listimagesoptions' => $this->makePartial('listimagesoptions', ['values' => $datas]),
        ];
    }

    public function updateSideBarreAttribute()
    {
        return \Redirect::refresh();
    }

    public function createFormWidget()
    {
        $config = $this->makeConfig('$/waka/utils/models/imagelist/fields.yaml');
        $config->alias = 'imagesListWidget';
        $config->arrayName = 'imageList_array';
        $config->model = new \Waka\Utils\Models\ImageList();
        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->bindToController();
        return $widget;
    }

    // public function createOptionWidget($type)
    // {
    //     $config = $this->makeConfig('$/waka/utils/models/imagelist/fields_'.$type.'.yaml');
    //     $config->alias = 'imageOptionsWidget';
    //     $config->arrayName = 'imageOptions_array';
    //     $config->model = new \Waka\Utils\Models\ImageList();
    //     $widget = $this->makeWidget('Backend\Widgets\Form', $config);
    //     $widget->bindToController();
    //     return $widget;
    // }
}
