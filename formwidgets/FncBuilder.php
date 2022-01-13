<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use Waka\Utils\Classes\Rules\FncBase;
use ApplicationException;
use ValidationException;
use Exception;
use Request;

/**
 * Fnc builder
 */
class FncBuilder extends FormWidgetBase
{
    use \Backend\Traits\FormModelWidget;

    //
    // Configurable properties
    //


    //
    // Object properties
    //

    /**
     * @var mixed Fncs cache
     */
    protected $fncsCache = false;

    /**
     * @var Backend\Widgets\Form
     */
    protected $fncFormWidget;

    protected $type = 'fncs';

    protected $targetProductor = null;

    protected $full_access = 'noBody';

    public $restrictedMode = true;

    public $autoSave = true;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->fillFromConfig([
            'targetProductor',
            'full_access',
            'autoSave',
        ]);

        if ($widget = $this->makeFncFormWidget()) {
            $widget->bindToController();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function loadAssets()
    {
        $this->addJs('js/fncs.js');
        $this->addCss('../../../../../wcli/wconfig/assets/css/formwidgets/fncs.css');
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('fncs_container');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['formModel'] = $this->model;
        $this->vars['fncs'] = $this->getFncs();
        $this->vars['isRestrictedMode'] = $this->isRestrictedMode();
        $this->vars['targetProductor'] = $this->targetProductor;
        $this->vars['fncFormWidget'] = $this->fncFormWidget;
        $this->getAvailableTags();
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        $this->model->bindEvent('model.afterSave', function() {
            $this->processSave();
        });

        return FormField::NO_SAVE_DATA;
    }

    public function autoSAve() {
        if($this->autoSave) {
            $this->processSave();
        }
    }

    public function isRestrictedMode() {
        $user = \BackendAuth::getUser();
        if($user->hasAccess($this->full_access)) {
            return false;
        } else {
             return true;
        }

    }

    protected function processSave()
    {
        $cache = $this->getCacheFncDataPayload();

        foreach ($cache as $id => $data) {
            $fnc = $this->findFncObj($id);

            if ($attributes = $this->getCacheFncAttributes($fnc)) {
                $fnc->fill($attributes);
            }

            $fnc->save(null, $this->sessionKey);
        }
    }

    //
    // AJAX
    //

    public function onLoadCreateFncForm()
    {
        //trace_log($this->targetProductor);
        try {
            $fncs = FncBase::findFncs($this->targetProductor, $this->model->data_source);
            $this->vars['fncs'] = $fncs;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('create_fnc_form');
    }

    public function restoreRestrictedField($fnc) {
        if(!$this->isRestrictedMode()) {
            return [];
        }
        $oldDatas = $this->getCacheFncAttributes($fnc);
        //trace_log("oldDatas to restore");
        //trace_log($oldDatas);
        $restrictedFields = $fnc->getRestrictedFields();
        $dataRestricted = [];
        foreach($oldDatas as $key=>$data) {
            if(in_array($key, $restrictedFields)) {
                $dataRestricted[$key] = $data;
            }
        }
        return $dataRestricted;

    }

    public function onSaveFnc()
    {
        $this->restoreCacheFncDataPayload();

        $fnc = $this->findFncObj();

        $oldData = $this->restoreRestrictedField($fnc);

        $data = post('Fnc', []);
        
        $jsonableField = $fnc->jsonable;
        foreach($jsonableField as $json) {
            $keyIsOk = $data[$json] ?? false;
            if(!$keyIsOk) {
                //trace_log('on vide');
                //Si le champs est vide on va le remettre dans le tableau. 
                $data[$json] = [];
            }
        }

        $data = array_merge($data, $oldData);

        $fnc->fill($data);
        $fnc->validate();
    
        $fnc->fnc_text = $fnc->getSubFormObject()->getText();

        $fnc->applyCustomData();

        $this->setCacheFncData($fnc);

        $this->autoSAve();

        return $this->renderFncs($fnc);
    }

    public function onLoadFncSetup()
    {
        try {
            $fnc = $this->findFncObj();

            $data = $this->getCacheFncAttributes($fnc);
            //trace_log("onLoadFncSetup dataCache");
            //trace_log($data);

            $this->fncFormWidget->setFormValues($data);

            $this->prepareVars();
            $this->vars['fnc'] = $fnc;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('fnc_settings_form');
    }

    public function onCreateFnc()
    {
        if (!$className = post('fnc_class')) {
            throw new ApplicationException('Please specify an fnc');
        }

        $this->restoreCacheFncDataPayload();

        $newFnc = $this->getRelationModel();
        $newFnc->fnceable_type = get_class($this->model);
        $newFnc->fnceable_id = $this->model->id;
        $newFnc->class_name = $className;
        $newFnc->save();

        $this->model->rule_fncs()->add($newFnc, post('_session_key'));

        $this->vars['newFncId'] = $newFnc->id;

        return $this->renderFncs();
    }

    public function onDeleteFnc()
    {
        $fnc = $this->findFncObj();

        if($this->autoSave) {
            $this->model->rule_fncs()->remove($fnc);
        } else {
            $this->model->rule_fncs()->remove($fnc, post('_session_key'));
        }
        return $this->renderFncs();
    }

    public function onReorderUpFnc()
    {
        $fnc = $this->findFncObj();
        $this->getNewOrderValue($fnc, true);
        return $this->renderFncs();
    }
    public function onReorderDownFnc()
    {
        $fnc = $this->findFncObj();
        $this->getNewOrderValue($fnc, false);
        return $this->renderFncs();
    }

    public function getNewOrderValue($fnc, $up = true) {
        $collection = $this->model->rule_fncs()->get();
        if($up) {
            $collection = $collection->reverse();
        }
        $nextFnc = false;
        foreach($collection as $testedFnc) {
            if($nextFnc) {
                $previousOrder = $fnc->sort_order;
                $fnc->sort_order = $testedFnc->sort_order;
                $this->model->rule_fncs()->save($fnc, post('_session_key'));
                $testedFnc->sort_order = $previousOrder;
                $this->model->rule_fncs()->save($testedFnc, post('_session_key'));
                return;
            }
            if($testedFnc->id == $fnc->id) {
                // $testedFnc->sort_order = $fnc->sort_order;
                // $this->model->rule_fncs()->save($testedFnc, post('_session_key'));
                $nextFnc = $testedFnc;
            }
            
        }
        return $fnc->sort_order;
    }

    public function onCancelFncSettings()
    {
        $fnc = $this->findFncObj(post('new_fnc_id'));

        $fnc->delete();

        return $this->renderFncs();
    }

    //
    // Postback deferring
    //

    public function getCacheFncTitle($fnc)
    {
        return array_get($this->getCacheFncData($fnc), 'title');
    }

    public function getCacheFncAttributes($fnc)
    {
        $attributes = array_get($this->getCacheFncData($fnc), 'attributes');
        $code = array_get($this->getCacheFncData($fnc), 'code');
        $photos = array_get($this->getCacheFncData($fnc), 'photos');
        $photo = array_get($this->getCacheFncData($fnc), 'photo');
        return array_merge($attributes, ["code" => $code]);
    }

    

    public function getCacheFncCode($fnc)
    {
        return array_get($this->getCacheFncData($fnc), 'code') ?? 'ERROR';
    }

    public function getCacheFncText($fnc)
    {
        $fncText =  array_get($this->getCacheFncData($fnc), 'text');
        return $fncText;
    }

    public function getCacheFncData($fnc, $default = null)
    {
        //trace_log("getCacheFncData");
        $cache = post('fnc_data', []);

        if (is_array($cache) && array_key_exists($fnc->id, $cache)) {
            return json_decode($cache[$fnc->id], true);
        }

        if ($default === false) {
            return null;
        }

        return $this->makeCacheFncData($fnc);
    }

    public function makeCacheFncData($fnc)
    {
        $data = [
            'attributes' => $fnc->config_data,
            'title' => $fnc->getTitle(),
            'text' => $fnc->getText(),
            'sort_order' => $fnc->sort_order,
            'photo' => $fnc->photo,
            'photos' => $fnc->photos,
            'code' => $fnc->code
        ];
        return $data;
    }

    public function setCacheFncData($fnc)
    {
        $cache = post('fnc_data', []);

        $cache[$fnc->id] = json_encode($this->makeCacheFncData($fnc), JSON_UNESCAPED_SLASHES);
        //trace_log($cache[$fnc->id]);

        Request::merge([
            'fnc_data' => $cache
        ]);
    }

    public function restoreCacheFncDataPayload()
    {
        //trace_log("restoreCacheFncDataPayload");
        Request::merge([
            'fnc_data' => json_decode(post('current_fnc_data'), true)
        ]);
    }

    public function getCacheFncDataPayload()
    {
        return post('fnc_data', []);
    }

    //
    // Helpers
    //

    protected function getAvailableTags()
    {
        if (!$fnc = $this->findFncObj(null, false)) {
            return null;
        }
        $attributes = new \Waka\utils\Classes\Wattributes($this->model, $this->type);
        $this->vars['keyFnc'] = $fnc->getCode();
        $this->vars['fncAttributs'] = $attributes->getFncOutput($fnc);
    }

    /**
     * Updates the primary rule fncs container
     * @return array
     */
    protected function renderFncs()
    {
        $this->prepareVars();

        return [
            '#'.$this->getId() => $this->makePartial('fncs')
        ];
    }

    protected function makeFncFormWidget()
    {
        if ($this->fncFormWidget !== null) {
            return $this->fncFormWidget;
        }

        if (!$model = $this->findFncObj(null, false)) {
            return null;
        }

        if (!$model->hasFieldConfig()) {
            return null;
        }

        //trace_log('makeFncFormWidget----------------');

        $config = $model->getFieldConfig($this->isRestrictedMode());
        $config->model = $model;
        $config->alias = $this->alias . 'Form';
        $config->arrayName = 'Fnc';

        $widget = $this->makeWidget('Backend\Widgets\Form', $config);

        return $this->fncFormWidget = $widget;
    }

    protected function getFncs()
    {
        if ($this->fncsCache !== false) {
            return $this->fncsCache;
        }
        

        $relationObject = $this->getRelationObject();
        $fncs = $relationObject->withDeferred($this->sessionKey)->get()->sortby('sort_order');

        return $this->fncsCache = $fncs ?: null;
    }

    protected function findFncObj($fncId = null, $throw = true)
    {
        $fncId = $fncId ? $fncId : post('current_fnc_id');

        $fnc = null;

        if (strlen($fncId)) {
            $fnc = $this->getRelationModel()->find($fncId);
        }

        if ($throw && !$fnc) {
            throw new ApplicationException('Fnc not found');
        }

        return $fnc;
    }
}
