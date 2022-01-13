<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use Waka\Utils\Classes\Rules\AskBase;
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
        $this->addCss('../../../../../wcli/wconfig/assets/css/formwidgets/asks.css');
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
        $this->vars['askFormWidget'] = $this->askFormWidget;
        $this->vars['attributesArray'] = $this->getAvailableTags();
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
            $asks = AskBase::findAsks($this->targetProductor);
            //trace_log('je load');
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

        //On remet le champs vide de JSON pour eviter de remettre les oldData dans le champs json.
        $jsonableField = $ask->jsonable;
        foreach($jsonableField as $json) {
            if(!$data[$json] ?? false ) {
                $data[$json] = [];
            }
        }

        $data = array_merge($data, $oldData);

        $ask->fill($data);
        $ask->validate();
    
        $ask->ask_text = $ask->getSubFormObject()->getText();

        $ask->applyCustomData();

        $this->setCacheAskData($ask);

        $this->autoSAve();

        return $this->renderAsks($ask);
    }

    public function onLoadAskSetup()
    {
        try {
            $ask = $this->findAskObj();
            $data = json_encode($this->getCacheAskAttributes($ask));
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

        $tempModel = new $className;
        $defaultValues = $tempModel->getDefaultValues();
        $newAsk->fill($defaultValues);
        //Je suis obligé de sauver 2 fois...sinon pas instancié et data est inconnu
        $newAsk->save();

        $this->vars['newAskId'] = $newAsk->id;
        //Sauvegarde auto
        $this->autoSAve();

        return $this->renderAsks();
    }

    public function onDeleteAsk()
    {
        $ask = $this->findAskObj();
        if($this->autoSave) {
            $this->model->rule_asks()->remove($ask);
        } else {
            $this->model->rule_asks()->remove($ask, post('_session_key'));
        }
        return $this->renderAsks();
    }

    public function onReorderUpAsk()
    {
        $ask = $this->findAskObj();
        $this->getNewOrderValue($ask, true);
        return $this->renderAsks();
    }
    public function onReorderDownAsk()
    {
        $ask = $this->findAskObj();
        $this->getNewOrderValue($ask, false);
        return $this->renderAsks();
    }

    public function getNewOrderValue($ask, $up = true) {
        $collection = $this->model->rule_asks()->get();
        if($up) {
            $collection = $collection->reverse();
        }
        $nextAsk = false;
        foreach($collection as $testedAsk) {
            if($nextAsk) {
                $previousOrder = $ask->sort_order;
                $ask->sort_order = $testedAsk->sort_order;
                $this->model->rule_asks()->save($ask, post('_session_key'));
                $testedAsk->sort_order = $previousOrder;
                $this->model->rule_asks()->save($testedAsk, post('_session_key'));
                return;
            }
            if($testedAsk->id == $ask->id) {
                $nextAsk = $testedAsk;
            }
            
        }
        return $ask->sort_order;
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
    public function getCacheAskCode($ask)
    {
        return array_get($this->getCacheAskData($ask), 'code') ?? 'ERROR';
    }

    public function getCacheAskAttributes($ask)
    {
        $attributes = array_get($this->getCacheAskData($ask), 'attributes');
        $code = array_get($this->getCacheAskData($ask), 'code');
        $photos = array_get($this->getCacheAskData($ask), 'photos');
        $photo = array_get($this->getCacheAskData($ask), 'photo');
        return array_merge($attributes, ["code" => $code]);
    }

    public function getCacheAskTitle($ask)
    {
        return array_get($this->getCacheAskData($ask), 'title');
    }

    public function getCacheAskText($ask)
    {
        $askText =  array_get($this->getCacheAskData($ask), 'text');
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
        $data = [
            'attributes' => $ask->config_data,
            'title' => $ask->getTitle(),
            'text' => $ask->getText(),
            'sort_order' => $ask->sort_order,
            'photo' => $ask->photo,
            'photos' => $ask->photos,
            'code' => $ask->code
        ];
        return $data;
    }

    public function setCacheAskData($ask)
    {
        //trace_log('setCacheAskData');
        $cache = post('ask_data', []);
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
        if (!$ask = $this->findAskObj(null, false)) {
            return null;
        }
        if(!$ask->showAttribute()) {
            return null;
        }
        $attributes = new \Waka\utils\Classes\Wattributes($this->model, $this->type);
        //trace_log($attributes->getAttributes());
        return  $attributes->getAttributes();
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
        $asks = $relationObject->withDeferred($this->sessionKey)->get()->sortby('sort_order');
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
