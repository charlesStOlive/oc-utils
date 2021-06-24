<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Waka\Utils\Classes\DataSource;

/**
 * FunctionsList Form Widget
 */
class FunctionsList extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_utils_functions_list';

    public $jsonValues;
    public $attributeWidget;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->attributeWidget = $this->createFormWidget();
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
    public function getDataSource()
    {
        return new DataSource($this->model->data_source);
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $noFunction = true;
        $ds = $this->getDataSource();
        $functionClass = $ds->getFunctionClass();
        if ($functionClass) {
            $noFunction = false;
        }
        $this->jsonValues = $this->getLoadValue();
        $this->vars['noFunction'] = $noFunction;
        $this->vars['functionClass'] = $functionClass;
        $this->vars['name'] = $this->formField->getName();
        $this->vars['user'] = \BackendAuth::getUser();
        //trace_log($this->getLoadValue());
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
        return \Backend\Classes\FormField::NO_SAVE_DATA;
    }

    public function onShowFunctions()
    {
        //recuperation de la classe function du data_source
        $ds = $this->getDataSource();
        $fnc_class = new $ds->editFunctions;

        //liste des fonctions de la classe
        $this->vars['functionList'] = $fnc_class->getFunctionsList();

        return $this->makePartial('popup');
    }

    public function onChooseFunction()
    {
        //recuperation de la fonction
        $functionCode = post('functionCode');

        //recuperation de la classe function du data_source et des attributs de la fonction
        $ds = $this->getDataSource();
        $fnc_class = new $ds->editFunctions;
        $attributes = $fnc_class->getFunctionAttribute($functionCode);

        $functionList = $fnc_class->getFunctionsList();

        //création du widget
        //$this->attributeWidget = $this->createFormWidget();

        //Ajout du nom par defaut
        $this->attributeWidget->getField('name')->value = $functionList[$functionCode];

        //ajout des whamps via les attributs

        if ($attributes) {
            foreach ($attributes as $key => $value) {
                $this->attributeWidget->addFields([$key => $value]);
            }
        }

        $this->vars['attributeWidget'] = $this->attributeWidget;

        //trace_log($attributes);
        $this->vars['attributes'] = $attributes;
        return [
            '#functionAttribute' => $this->makePartial('attributes'),
        ];
    }
    public function onCreateFunctionValidation()
    {
        //mis d'en une collection des données existantes
        //trace_log(post());
        $data;
        $modelValues = $this->getLoadValue();
        if ($modelValues && count($modelValues)) {
            $datas = new \Winter\Storm\Support\Collection($modelValues);
        } else {
            $datas = new \Winter\Storm\Support\Collection();
        }

        //preparatio de l'array a ajouter
        $widgetArray = post('attributes_array');
        //ajout du code qui n'est pas dans le widget_array
        $widgetArray['functionCode'] = post('functionCode');
        $datas->push($widgetArray);

        //enregistrement du model
        $field = $this->fieldName;
        $this->model[$field] = $datas;
        $this->model->save();

        //rafraichissement de la liste
        return [
            '#list' => $this->makePartial('list', ['values' => $datas]),
        ];
    }
    public function onUpdateFunction()
    {

        $collectionCode = post('collectionCode');
        $functionCode = post('functionCode');
        //trace_log($functionCode);

        $modelValues = $this->getLoadValue();
        //trace_log($modelValues);
        $datas = new \Winter\Storm\Support\Collection($modelValues);
        $data = $datas->where('collectionCode', $collectionCode)->first();

        $ds = $this->getDataSource();
        $fnc_class = new $ds->editFunctions;
        $attributes = $fnc_class->getFunctionAttribute($functionCode);

        //trace_log($data);

        //création du widget

        $this->attributeWidget->getField('collectionCode')->value = $data['collectionCode'];
        if(!\BackendAuth::getUser()->hasAccess('waka.formwidget.functionlist.admin')) {
            $this->attributeWidget->getField('collectionCode')->readOnly = true;
        }
        
        $this->attributeWidget->getField('name')->value = $data['name'];
        
        if ($attributes) {
            foreach ($attributes as $key => $value) {
                $this->attributeWidget->addFields([$key => $value]);
                $type = $value['type'] ?? false;
                if ($type == 'taglist') {
                    $val = $data[$key] ?? [];
                    $this->attributeWidget->getField($key)->value = implode(",", $val);
                } else {
                    $this->attributeWidget->getField($key)->value = $data[$key] ?? null;
                }
            }
        }

        $this->vars['collectionCode'] = $collectionCode;
        $this->vars['functionCode'] = $functionCode;
        $this->vars['attributeWidget'] = $this->attributeWidget;

        return $this->makePartial('popup_update');
    }
    public function onDeleteFunction()
    {

        $collectionCode = post('collectionCode');
        $datas = $this->getLoadValue();

        $updatedDatas = [];
        foreach ($datas as $key => $data) {
            if ($data['collectionCode'] != $collectionCode) {
                $updatedDatas[$key] = $data;
            }
        }

        //enregistrement du model
        $field = $this->fieldName;
        $this->model[$field] = $updatedDatas;
        $this->model->save();

        return [
            '#list' => $this->makePartial('list', ['values' => $updatedDatas]),
        ];
    }
    public function onUpdateFunctionValidation()
    {
        //On range collection code hidden das oldCollectionCode au cas ou le user change le collectionCode qui est notre clé
        $OldCollectionCode = post('collectionCode');
        $functionCode = post('functionCode');

        //mis d'en une collection des données existantes
        $datas = $this->getLoadValue();

        //preparatio de l'array a ajouter
        $widgetArray = post('attributes_array');
        //ajout du code qui n'est pas dans le widget_array
        $widgetArray['functionCode'] = post('functionCode');

        foreach ($datas as $key => $data) {
            if ($data['collectionCode'] == $OldCollectionCode) {
                $datas[$key] = $widgetArray;
            }
        }

        //enregistrement du model
        $field = $this->fieldName;
        $this->model[$field] = $datas;
        $this->model->save();

        //rafraichissement de la liste
        return [
            '#list' => $this->makePartial('list', ['values' => $datas]),
        ];
    }

    public function createFormWidget()
    {
        $config = $this->makeConfig('$/waka/utils/models/scopefunction/fields.yaml');
        $config->alias = 'attributeWidget';
        $config->arrayName = 'attributes_array';
        $config->model = new \Waka\Utils\Models\ScopeFunction();
        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->bindToController();
        return $widget;
    }
}
