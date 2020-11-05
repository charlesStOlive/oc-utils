<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;
use Flash;
use Lang;
use October\Rain\Support\Collection;
use Redirect;
use Session;

class TraitementsLots extends ControllerBehavior
{
    /**
     * @inheritDoc
     */
    protected $requiredProperties = ['lotsConfig'];

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     */
    protected $requiredConfig = ['modelClass', 'form'];

    /**
     * @inheritDoc
     */

    protected $lotsWidget;

    public function __construct($controller)
    {
        parent::__construct($controller);
        $this->config = $this->makeConfig($controller->lotsConfig, $this->requiredConfig);
        $this->lotsWidget = $this->createLotsWidgetFormWidget();
    }

    /**
     * Methodes
     */
    public function onLoadActionPopup()
    {
        $model = $this->getConfig('modelClass');
        //

        //traitement de l'ensemble
        $allEnabled = $this->getConfig('allEnabled');
        $this->vars['allEnabled'] = $allEnabled;
        if ($allEnabled) {
            $allCount = $model::count();
            $this->vars['allCount'] = $model::count();
        }

        //traitement des query filtre
        $filteredEnabled = $this->getConfig('filteredEnabled');
        $this->vars['filteredEnabled'] = $filteredEnabled;
        if ($filteredEnabled) {
            $lists = $this->controller->makeLists();
            $widget = $lists[0] ?? reset($lists);
            $query = $widget->prepareQuery();
            $results = $query->get();
            Session::put('lots.filteredIds', $results->lists('id'));
            $this->vars['filteredCount'] = $query->count();
        }

        //traitement des cases à cocher
        $checkedIds = post('checked');
        $checkedCount = null;
        if (is_countable($checkedIds)) {
            $checkedCount = count($checkedIds);
        }
        if ($checkedCount) {
            Session::put('lots.checkedIds', $checkedIds);

        }
        $this->vars['checkedCount'] = $checkedCount;

        //Le popup

        $this->vars['model'] = $model;
        $this->vars['lotsWidget'] = $this->lotsWidget;
        return $this->makePartial('$/waka/utils/behaviors/traitementslots/_popup.htm');
    }

    public function onLotsValidation()
    {
        $data = $this->lotsWidget->getSaveData();
        $modelName = $this->getConfig('modelClass');
        $updateArray = [];
        //trace_log($data);

        $listType = post('listType');
        $listId = null;
        if ($listType == 'filtered') {
            $listId = Session::get('modelImportExportLog.listId');
        } elseif ($listType == 'checked') {
            $listId = Session::get('modelImportExportLog.checkedIds');
        } elseif ($listType == 'all') {

        }
        Session::forget('modelImportExportLog.listId');
        Session::forget('modelImportExportLog.checkedIds');

        $model = new $modelName;
        $models;

        if ($listId) {
            $models = $modelName::whereIn('id', $listId)->get();
        } else {
            $models = $modelName::get();
        }
        foreach ($models as $model) {
            foreach ($data as $key => $boolVal) {
                $paramField = ends_with($key, '_wlot_enabled');
                if ($paramField && $boolVal) {
                    $fieldKey = str_replace('_wlot_enabled', "", $key);
                    $model[$fieldKey] = $data[$fieldKey];
                    $model->save();
                    //$updateArray[$fieldKey] = $data[$fieldKey];
                }
            }
        }

        Flash::info("Traitement effectué");
        return Redirect::refresh();
        //return true;

    }

    public function createLotsWidgetFormWidget()
    {
        $configLots = $this->getConfig('form');
        $configLots = $this->addCheckBoxToConfig($configLots);

        $config = $this->makeConfigFromArray($configLots);
        $config->alias = 'myLotsformWidget';

        $config->arrayName = 'lots_array';
        //$config->redirect = $this->getConfig('redirect').':id';

        $modelName = $this->getConfig('modelClass');
        $config->model = new $modelName;

        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->bindToController();

        return $widget;
    }

    public function addCheckBoxToConfig($config)
    {
        $fields = new Collection($config['fields']);
        $triggerElements = [];
        $i = 100;
        $fields = $fields->map(function ($item, $key) use ($triggerElements, $i) {
            $keyName = $key . '_wlot_enabled';
            $triggerOption = [
                'action' => 'enable',
                'field' => $keyName,
                'condition' => 'checked',
            ];
            $item['trigger'] = $triggerOption;
            $item['wk_order'] = $i;
            $i++;

            return $item;
            //
        });
        $i = 1;
        foreach ($fields as $key => $field) {
            $keyName = $key . '_wlot_enabled';
            $obj = [
                'label' => Lang::get('waka.utils::lang.popup.change_field') . ' : ' . Lang::get($field['label']),
                'type' => 'checkbox',
                'wk_order' => $i,
            ];
            $i++;
            $fields->put($keyName, $obj);
        }
        //trace_log($fields->toArray());

        return [
            'fields' => $fields->sortBy('wk_order')->toArray(),
        ];

    }
}
