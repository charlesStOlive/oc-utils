<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * ScopesList Form Widget
 */
class ScopesList extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_utils_scopes_list';

    public $jsonValues;

    public $scopesType = [
        'model_value' => [
            'label' => "Restriction depuis une valeur d'un champ",
            'config' => 'scope_value',
        ],

        'model_values' => [
            'label' => "Restriction sur plusieurs valeurs d'un champ",
            'config' => 'scope_values',
        ],
        'model_relation' => [
            'label' => "Restriction en fonction d'une relation",
            'config' => 'scope_relation',
        ],
        'model_bool' => [
            'label' => "Restriction Vrai/Faux d'un champ",
            'config' => 'scope_bool',
        ],
        'user' => [
            'label' => "Restriction lié à l'utilisateur",
            'config' => 'scope_users',
        ],
    ];

    /**
     * @inheritDoc
     */
    public function init()
    {
    }

    public $scopeClass;

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('scopeslist');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->jsonValues = $this->getLoadValue();
        // $this->vars['noScope'] = $noScope;
        // $this->vars['scopeClass'] = $scopeClass;
        $this->vars['name'] = $this->formField->getName();
        //trace_log($this->getLoadValue());
        $this->vars['values'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;

    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->addCss('css/scopeslist.css', 'Waka.Utils');
        $this->addJs('js/scopeslist.js', 'Waka.Utils');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return \Backend\Classes\FormField::NO_SAVE_DATA;
    }

    public function onShowScopes()
    {
        //liste des fonctions de la classe
        $this->vars['scopesType'] = $this->scopesType;

        return $this->makePartial('popup');

    }

    public function onChooseScope()
    {

        $scopeKey = post('scopeKey');
        $scope = $this->scopesType[$scopeKey];

        $attributeWidget = $this->createFormWidget($scope['config']);
        $attributeWidget->getField('name')->value = $scope['label'];
        $this->vars['attributeWidget'] = $attributeWidget;
        return [
            '#scopeAttribute' => $this->makePartial('attributes'),
        ];

    }
    public function onCreateScopeValidation()
    {
        //preparatio de l'array a ajouter
        $widgetArray = post('attributes_array');
        //ajout du code qui n'est pas dans le widget_array
        $widgetArray['scopeCode'] = uniqid();
        $widgetArray['scopeKey'] = post('scopeKey');

        $data;
        $modelValues = $this->getLoadValue();
        if ($modelValues && count($modelValues)) {
            $datas = new \October\Rain\Support\Collection($modelValues);
        } else {
            $datas = new \October\Rain\Support\Collection();
        }
        $datas->push($widgetArray);

        //enregistrement du model
        $field = $this->fieldName;
        $this->model[$field] = $datas;
        $this->model->save();

        //rafraichissement de la liste
        return [
            '#scopeList' => $this->makePartial('list', ['values' => $datas]),
        ];
    }
    public function onUpdateScope()
    {

        $scopeKey = post('scopeKey');
        $scopeCode = post('scopeCode');
        //trace_log($functionCode);

        $modelValues = $this->getLoadValue();
        //trace_log($modelValues);
        $datas = new \October\Rain\Support\Collection($modelValues);
        $dataScope = $datas->where('scopeCode', $scopeCode)->first();

        //création du widget
        $scopeKey = post('scopeKey');
        $scope = $this->scopesType[$scopeKey];
        $attributeWidget = $this->createFormWidget($scope['config']);

        foreach ($dataScope as $key => $value) {
            $field = $attributeWidget->getField($key);
            if ($field) {
                $attributeWidget->getField($key)->value = $value;
            }

        }

        $this->vars['scopeKey'] = $scopeKey;
        $this->vars['scopeCode'] = $scopeCode;
        $this->vars['attributeWidget'] = $attributeWidget;

        return $this->makePartial('popup_update');

    }
    public function onDeleteScope()
    {

        $scopeCode = post('scopeCode');
        $datas = $this->getLoadValue();

        $updatedDatas = [];
        foreach ($datas as $key => $data) {
            if ($data['scopeCode'] != $scopeCode) {
                $updatedDatas[$key] = $data;
            }
        }

        //enregistrement du model
        $field = $this->fieldName;
        $this->model[$field] = $updatedDatas;
        $this->model->save();

        return [
            '#scopeList' => $this->makePartial('list', ['values' => $updatedDatas]),
        ];

    }
    public function onUpdateScopeValidation()
    {
        //On range collection code hidden das oldCollectionCode au cas ou le user change le collectionCode qui est notre clé;
        $scopeCode = post('scopeCode');
        $scopeKey = post('scopeKey');

        //mis d'en une collection des données existantes
        $datas = $this->getLoadValue();

        //preparatio de l'array a ajouter
        $widgetArray = post('attributes_array');

        foreach ($datas as $key => $data) {
            if ($data['scopeCode'] == $scopeCode) {
                $datas[$key] = $widgetArray;
                $datas[$key]['scopeCode'] = $scopeCode;
                $datas[$key]['scopeKey'] = $scopeKey;

            }
        }

        //enregistrement du model
        $field = $this->fieldName;
        $this->model[$field] = $datas;
        $this->model->save();

        //rafraichissement de la liste
        return [
            '#scopeList' => $this->makePartial('list', ['values' => $datas]),
        ];
    }

    public function createFormWidget($yaml)
    {
        $config = $this->makeConfig('$/waka/utils/models/scopefunction/' . $yaml . '.yaml');
        $config->alias = 'attributeWidget';
        $config->arrayName = 'attributes_array';
        $config->model = new \Waka\Utils\Models\ScopeFunction();
        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->bindToController();
        return $widget;
    }
}
