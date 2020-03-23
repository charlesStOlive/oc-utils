<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;

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
        return $this->makePartial('functionslist');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->jsonValues = $this->getLoadValue();
        $this->vars['functionClass'] = $this->model->data_source->getFunctionClass();
        $this->vars['name'] = $this->formField->getName();
        trace_log($this->getLoadValue());
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
        $fnc_class = $this->model->data_source->getFunctionClass();

        //liste des fonctions de la classe
        $this->vars['functionList'] = $fnc_class->getFunctionsList();

        return $this->makePartial('popup');

    }

    public function onChooseFunction()
    {
        //recuperation de la fonction
        $functionCode = post('functionCode');

        //recuperation de la classe function du data_source et des attributs de la fonction
        $fnc_class = $this->model->data_source->getFunctionClass();
        $attributes = $fnc_class->getFunctionAttribute($functionCode);

        //création du widget
        $attributeWidget = $this->createFormWidget();
        //ajout des whamps via les attributs

        if ($attributes) {
            foreach ($attributes as $key => $value) {
                trace_log($value['options'] ?? null);
                $attributeWidget->addFields([
                    $key => [
                        'label' => $value['label'],
                        'type' => $value['type'],
                        'options' => $value['options'] ?? null,
                        'useKey' => true,
                    ],
                ]);
            }

        }

        $this->vars['attributeWidget'] = $attributeWidget;

        trace_log($attributes);
        $this->vars['attributes'] = $attributes;
        return [
            '#functionAttribute' => $this->makePartial('attributes'),
        ];

    }
    public function onCreateFunctionValidation()
    {
        //mis d'en une collection des données existantes
        trace_log(post());
        $data;
        $modelValues = $this->getLoadValue();
        if ($modelValues && count($modelValues)) {
            $datas = new \October\Rain\Support\Collection($modelValues);
        } else {
            $datas = new \October\Rain\Support\Collection();
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
        trace_log($functionCode);

        $modelValues = $this->getLoadValue();
        trace_log($modelValues);
        $datas = new \October\Rain\Support\Collection($modelValues);
        $data = $datas->where('collectionCode', $collectionCode)->first();

        $fnc_class = $this->model->data_source->getFunctionClass();
        $attributes = $fnc_class->getFunctionAttribute($functionCode);

        trace_log($data);

        //création du widget
        $attributeWidget = $this->createFormWidget();
        $attributeWidget->getField('collectionCode')->value = $data['collectionCode'];
        $attributeWidget->getField('name')->value = $data['name'];

        if ($attributes) {
            foreach ($attributes as $key => $value) {
                $attributeWidget->addFields([
                    $key => [
                        'label' => $value['label'],
                        'type' => $value['type'],
                        'options' => $value['options'] ?? null,
                        'useKey' => true,
                    ],
                ]);
                if ($value['type'] == 'taglist') {
                    $attributeWidget->getField($key)->value = implode(",", $data[$key]);
                } else {
                    $attributeWidget->getField($key)->value = $data[$key];
                }

            }

        }

        $this->vars['collectionCode'] = $collectionCode;
        $this->vars['functionCode'] = $functionCode;
        $this->vars['attributeWidget'] = $attributeWidget;

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
