<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use Waka\Utils\Classes\AskBase;
use ApplicationException;
use ValidationException;
use Exception;
use Request;

/**
 * Ask builder
 */
class AskBuilder extends FormWidgetBase
{
    use \Backend\Traits\FormModelWidget;

    //
    // Configurable properties
    //


    //
    // Object properties
    //

    /**
     * @var mixed Asks cache
     */
    protected $asksCache = false;

    /**
     * @var Backend\Widgets\Form
     */
    protected $askFormWidget;

    protected $type = 'asks';

    protected $full_access = 'noBody';

    public $restrictedMode = true;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->fillFromConfig([
            'type',
            'full_access',
        ]);

        if ($widget = $this->makeAskFormWidget()) {
            $widget->bindToController();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function loadAssets()
    {
        $this->addJs('js/asks.js');
        $this->addCss('css/asks.css');
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('asks_container');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['formModel'] = $this->model;
        $this->vars['asks'] = $this->getAsks();
        $this->vars['isRestrictedMode'] = $this->isRestrictedMode();
        //trace_log($this->getAsks());
        $this->vars['askFormWidget'] = $this->askFormWidget;
        $this->vars['availableTags'] = $this->getAvailableTags();
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
        $cache = $this->getCacheAskDataPayload();

        foreach ($cache as $id => $data) {
            $ask = $this->findAskObj($id);

            if ($attributes = $this->getCacheAskAttributes($ask)) {
                $ask->fill($attributes);
            }

            $ask->save(null, $this->sessionKey);
        }
    }

    //
    // AJAX
    //

    public function onLoadCreateAskForm()
    {
        try {
            $asks = AskBase::findAsks($this->type);
            $this->vars['asks'] = $asks;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('create_ask_form');
    }

    public function restoreRestrictedField($ask) {
        if(!$this->isRestrictedMode()) {
            return [];
        }
        $oldDatas = $this->getCacheAskAttributes($ask);
        //trace_log("oldDatas to restore");
        //trace_log($oldDatas);
        $restrictedFields = $ask->getRestrictedFields();
        $dataRestricted = [];
        foreach($oldDatas as $key=>$data) {
            if(in_array($key, $restrictedFields)) {
                $dataRestricted[$key] = $data;
            }
        }
        return $dataRestricted;

    }

    public function onSaveAsk()
    {
        // trace_log("On Save ASK");
       

        $this->restoreCacheAskDataPayload();

        $ask = $this->findAskObj();

        $oldData = $this->restoreRestrictedField($ask);

        $data = post('Ask', []);
        //trace_log("posted data");
        //trace_log($data);
        $data = array_merge($data, $oldData);
        //trace_log("Data to save");
        //trace_log($data);

        $ask->fill($data);
        $ask->validate();
    
        $ask->ask_text = $ask->getAskObject()->getText();

        $ask->applyCustomData();

        $this->setCacheAskData($ask);

        return $this->renderAsks($ask);
    }

    public function onLoadAskSetup()
    {
        try {
            $ask = $this->findAskObj();

            $data = $this->getCacheAskAttributes($ask);
            //trace_log("onLoadAskSetup dataCache");
            //trace_log($data);

            $this->askFormWidget->setFormValues($data);

            $this->prepareVars();
            $this->vars['ask'] = $ask;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('ask_settings_form');
    }

    public function onCreateAsk()
    {
        if (!$className = post('ask_class')) {
            throw new ApplicationException('Please specify an ask');
        }

        $this->restoreCacheAskDataPayload();

        $newAsk = $this->getRelationModel();
        $newAsk->askeable_type = get_class($this->model);
        $newAsk->askeable_id = $this->model->id;
        $newAsk->class_name = $className;
        $newAsk->save();

        $this->model->rule_asks()->add($newAsk, post('_session_key'));

        $this->vars['newAskId'] = $newAsk->id;

        return $this->renderAsks();
    }

    public function onDeleteAsk()
    {
        $ask = $this->findAskObj();

        $this->model->rule_asks()->remove($ask, post('_session_key'));

        return $this->renderAsks();
    }

    public function onCancelAskSettings()
    {
        $ask = $this->findAskObj(post('new_ask_id'));

        $ask->delete();

        return $this->renderAsks();
    }

    //
    // Postback deferring
    //

    public function getCacheAskAttributes($ask)
    {
        return array_get($this->getCacheAskData($ask), 'attributes');
    }

    public function getCacheAskTitle($ask)
    {
        return array_get($this->getCacheAskData($ask), 'title');
    }

    public function getCacheAskText($ask)
    {
        //trace_log('getCacheAskText---');
        //trace_log($this->getCacheAskData($ask));
        $askText =  array_get($this->getCacheAskData($ask), 'text');
        //trace_log("actopn text : ".$askText);
        return $askText;
    }

    public function getCacheAskData($ask, $default = null)
    {
        $cache = post('ask_data', []);

        if (is_array($cache) && array_key_exists($ask->id, $cache)) {
            return json_decode($cache[$ask->id], true);
        }

        if ($default === false) {
            return null;
        }

        return $this->makeCacheAskData($ask);
    }

    public function makeCacheAskData($ask)
    {
        //trace_log('makeCacheAskData');
        
        $data = [
            'attributes' => $ask->config_data,
            'title' => $ask->getTitle(),
            'text' => $ask->getText(),
        ];


        //trace_log($data);

        return $data;
    }

    public function setCacheAskData($ask)
    {
        $cache = post('ask_data', []);

        //trace_log($cache);

        $cache[$ask->id] = json_encode($this->makeCacheAskData($ask));

        Request::merge([
            'ask_data' => $cache
        ]);
    }

    public function restoreCacheAskDataPayload()
    {
        Request::merge([
            'ask_data' => json_decode(post('current_ask_data'), true)
        ]);
    }

    public function getCacheAskDataPayload()
    {
        return post('ask_data', []);
    }

    //
    // Helpers
    //

    protected function getAvailableTags()
    {
        $tags = [];

        if ($this->model->methodExists('defineParams')) {
            $params = $this->model->defineParams();

            foreach ($params as $param => $definition) {
                $tags[$param] = array_get($definition, 'label');
            }
        }

        return $tags;
    }

    /**
     * Updates the primary rule asks container
     * @return array
     */
    protected function renderAsks()
    {
        $this->prepareVars();

        return [
            '#'.$this->getId() => $this->makePartial('asks')
        ];
    }

    protected function makeAskFormWidget()
    {
        if ($this->askFormWidget !== null) {
            return $this->askFormWidget;
        }

        if (!$model = $this->findAskObj(null, false)) {
            return null;
        }

        if (!$model->hasFieldConfig()) {
            return null;
        }

        //trace_log('makeAskFormWidget----------------');

        $config = $model->getFieldConfig($this->isRestrictedMode());
        $config->model = $model;
        $config->alias = $this->alias . 'Form';
        $config->arrayName = 'Ask';

        $widget = $this->makeWidget('Backend\Widgets\Form', $config);

        return $this->askFormWidget = $widget;
    }

    protected function getAsks()
    {
        if ($this->asksCache !== false) {
            return $this->asksCache;
        }
        

        $relationObject = $this->getRelationObject();
        $asks = $relationObject->withDeferred($this->sessionKey)->get();

        return $this->asksCache = $asks ?: null;
    }

    protected function findAskObj($askId = null, $throw = true)
    {
        $askId = $askId ? $askId : post('current_ask_id');

        $ask = null;

        if (strlen($askId)) {
            $ask = $this->getRelationModel()->find($askId);
        }

        if ($throw && !$ask) {
            throw new ApplicationException('Ask not found');
        }

        return $ask;
    }
}
