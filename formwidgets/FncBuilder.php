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

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->fillFromConfig([
            'targetProductor',
            'full_access',
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
        //trace_log($this->getFncs());
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
        //trace_log($this->model->data_source);
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
        // trace_log("On Save ASK");
       

        $this->restoreCacheFncDataPayload();

        $fnc = $this->findFncObj();

        $oldData = $this->restoreRestrictedField($fnc);

        $data = post('Fnc', []);
        //trace_log("posted data");
        //trace_log($data);
        $data = array_merge($data, $oldData);
        //trace_log("Data to save");
        //trace_log($data);

        $fnc->fill($data);
        $fnc->validate();
    
        $fnc->fnc_text = $fnc->getFncObject()->getText();

        $fnc->applyCustomData();

        $this->setCacheFncData($fnc);

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

        $this->model->rule_fncs()->remove($fnc, post('_session_key'));

        return $this->renderFncs();
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

    public function getCacheFncAttributes($fnc)
    {
        return array_get($this->getCacheFncData($fnc), 'attributes');
    }

    public function getCacheFncTitle($fnc)
    {
        return array_get($this->getCacheFncData($fnc), 'title');
    }

    public function getCacheFncText($fnc)
    {
        //trace_log('getCacheFncText---');
        //trace_log($this->getCacheFncData($fnc));
        $fncText =  array_get($this->getCacheFncData($fnc), 'text');
        //trace_log("actopn text : ".$fncText);
        return $fncText;
    }

    public function getCacheFncData($fnc, $default = null)
    {
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
        //trace_log('makeCacheFncData');
        
        $data = [
            'attributes' => $fnc->config_data,
            'title' => $fnc->getTitle(),
            'text' => $fnc->getText(),
        ];


        //trace_log($data);

        return $data;
    }

    public function setCacheFncData($fnc)
    {
        $cache = post('fnc_data', []);

        //trace_log($cache);

        $cache[$fnc->id] = json_encode($this->makeCacheFncData($fnc));

        Request::merge([
            'fnc_data' => $cache
        ]);
    }

    public function restoreCacheFncDataPayload()
    {
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
        $fncs = $relationObject->withDeferred($this->sessionKey)->get();

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
