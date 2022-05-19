<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use ApplicationException;
use ValidationException;
use Exception;
use Request;

/**
 * Rule builder
 */
class RuleBuilder extends FormWidgetBase
{
    use \Backend\Traits\FormModelWidget;

    //
    // Configurable properties
    //
    protected $ruleMode = null;
    protected $targetProductor = null;
    protected $full_access = 'noBody';
    public $restrictedMode = true;
    public $autoSave = true;
    public $showAttributes = false;
    public $label = "waka.utils::lang.rules.label";
    public $prompt = "waka.utils::lang.rules.prompt";
    public $prompt_share = "waka.utils::lang.rules.prompt_share";
    //
    // Object properties
    //
    /**
     * @var mixed Rules cache
     */
    protected $rulesCache = false;
    /**
     * @var Backend\Widgets\Form
     */
    protected $ruleFormWidget;

    

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->fillFromConfig([
            'targetProductor',
            'full_access',
            'ruleMode',
            'label',
            'prompt',
            'prompt_share',
            'autoSave',
            'showAttributes'
        ]);

        if ($widget = $this->makeRuleFormWidget()) {
            $widget->bindToController();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function loadAssets()
    {
        $this->addJs('js/rules.js');
        $this->addCss('css/rules.css');
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->isConfigOk();
        $this->prepareVars();

        return $this->makePartial('rules_container');
    }
    public function isConfigOk() {
        if(!$this->targetProductor) {
            throw new ApplicationException('Il manque targetProductor dans la config');
        }
        if(!in_array($this->ruleMode, ['content', 'condition','fnc', 'ask', 'bloc', 'action', 'option'])) {
            throw new ApplicationException('ruleMode doit être égale a content/condition/fnc/ask/action');
        }
    }
    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['label'] = $this->label;
        $this->vars['prompt'] = $this->prompt;
        $this->vars['prompt_share'] = $this->prompt_share;
        //
        $this->vars['ruleMode'] = $this->ruleMode;
        $this->vars['formModel'] = $this->model;
        $this->vars['rules'] = $this->getRules();
        $this->vars['targetProductor'] = $this->targetProductor;
        $this->vars['ruleFormWidget'] = $this->ruleFormWidget;
        $this->vars['showAttributes'] = $this->showAttributes;
        //
        $this->vars['isRestrictedMode'] = $this->isRestrictedMode();
        //
        $this->vars['attributesArray'] = $this->getAvailableTags();
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        // $this->model->bindEvent('model.afterSave', function() {
        //     $this->processSave();
        // });

        return FormField::NO_SAVE_DATA;
    }

    // public function autoSAve() {
    //     if($this->autoSave) {
    //         $this->processSave();
    //     }
    // }

    public function isRestrictedMode() {
        $user = \BackendAuth::getUser();
        if($user->hasAccess($this->full_access)) {
            return false;
        } else {
             return true;
        }

    }

    // protected function processSave()
    // {
    //     $cache = $this->getCacheRuleDataPayload();
    //     foreach ($cache as $id => $data) {
    //         $rule = $this->findRuleObj($id);
    //         $attributes = $this->getCacheRuleAttributes($rule);
    //         if ($attributes) {
    //             $rule->fill($attributes);
    //         }
    //         $rule->save(null, $this->sessionKey);
    //         if($rule->is_share) {
    //             $this->saveSharedModel($rule, $attributes);
    //         }
    //     }
    // }
    public function saveSharedModel($rule, $attributes) {
        
        //trace_log($attributes);
        $modelsSharing = $this->getSharedModel($rule);
        foreach($modelsSharing as $model) {
            $model->fill($attributes);
            $model->save(null, $this->sessionKey);
        }
    }

    public function countShareModel($rule = null) {
        if($shares = $this->getSharedModel($rule)) {
            return $shares->count();
        }
        return null;
    }

    public function getSharedModel($rule = null) {
        //Si rule null la requete viens du dom on va chercher le rule avec postid.rule_class SINON ça vient de l'enregistrement on connait déjà la rule.
        if(!$rule) {
            $rule = $this->findRuleObj();
        }
        $shareMode = $rule->getShareMode();
        //trace_log("getSharedModel : ".$shareMode." rule : ".$rule->code);
        if(!$shareMode) {
            return ;
        }
        $class = get_class($rule);
        $eable = $this->ruleMode.'eable';
        $eable_type = $this->ruleMode.'eable_type';
        $eable_type_value = get_class($this->model);
        $className = $rule->class_name;
        $ds = $rule->getDs();
        $dsCode = null;
        if($ds) {
            $dsCode = $rule->getDs()->code;
        }
        
        $modelsSharing = $class::where($eable_type, $eable_type_value)->where('class_name', $className)->where('code', $rule->code)->where('is_share','<>', null);
        if($shareMode == 'ressource' && $dsCode) {
            $modelsSharing = $modelsSharing->whereHasMorph($eable, $eable_type_value,  function ($query) use($dsCode) {
                $query->where('data_source', $dsCode);
            });
        }
        if($shareMode == 'ressource' && !$dsCode) {
            return [];
        }
        //trace_log($modelsSharing->get()->count());
        //trace_log($modelsSharing->get());
        return $modelsSharing->get();

    }

    //
    // AJAX
    //

    /**
     * TODO armoniser les finRules et fnc
     */
    public function onLoadCreateRuleForm()
    {
        try {
            $rules = $this->getRuleClass()::findRules($this->ruleMode, $this->targetProductor, $this->model->data_source);
            $this->vars['rules'] = $rules;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
        return $this->makePartial('create_rule_form');
    }

    public function onLoadShareComponent()
    {
        try {
            $shares = $this->getRuleClass()::findShares($this->ruleMode, $this->model, $this->model->data_source);
            $this->vars['shares'] = $shares;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('add_share_form');
    }

    public function restoreRestrictedField($rule) {
        if(!$this->isRestrictedMode()) {
            return [];
        }
        $oldDatas = $this->getCacheRuleAttributes($rule);
        $restrictedFields = $rule->getRestrictedFields();
        $dataRestricted = [];
        foreach($oldDatas as $key=>$data) {
            if(in_array($key, $restrictedFields)) {
                $dataRestricted[$key] = $data;
            }
        }
        return $dataRestricted;

    }

    public function onSaveRule()
    {
        //trace_log('on save rule');
        //$this->restoreCacheRuleDataPayload();
        $rule = $this->findRuleObj();
        //$oldData = $this->restoreRestrictedField($rule);
        $data = post('Rule', []);
        //
        $jsonableField = $rule->jsonable;
        foreach($jsonableField as $json) {
            $keyIsOk = $data[$json] ?? false;
            if(!$keyIsOk) {
                //Si le champs est vide on va le remettre dans le tableau. 
                $data[$json] = [];
            }
        }
        //$data = array_merge($data, $oldData);
        //
        $rule->fill($data);
        $rule->validate();

        //$rule->rule_text = $rule->getSubFormObject()->getText();
        //$rule->applyCustomData();
        //trace_log($rule->toArray());
        $rule->save();
        //
        //$this->setCacheRuleData($rule);
        //$this->autoSAve();
        return $this->renderRules($rule->getType());
    }

    public function onLoadRuleSetup()
    {
        try {
            $rule = $this->findRuleObj();
            $data = json_encode($rule);
            $this->ruleFormWidget->setFormValues($data);
            $this->prepareVars();
            $this->vars['rule'] = $rule;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
        return $this->makePartial('rule_settings_form');
    }

    /**
     * TODO: modifier le fonctionement de {$this->ruleMode.'eable_type'}
     * Prendre la valeur dans $tempModel issue de classname
     */

    public function onCreateRule()
    {
        //trace_log($this->ruleMode);
        //trace_log(post());
        if (!$className = post('rule_class')) {
            throw new ApplicationException('Please specify an rule');
        }
        

        //$this->restoreCacheRuleDataPayload();
        //
        $newRule = $this->getRelationModel();
        //$newRule->mode = $this->mode;
        $newRule->{$this->ruleMode.'eable_type'} = get_class($this->model);
        $newRule->{$this->ruleMode.'eable_id'} = $this->model->id;
        $newRule->class_name = $className;
        $newRule->forceSave();

        $this->getRuleRelation()->add($newRule, post('_session_key'));

        $tempModel = new $className;
        $defaultValues = $newRule->getDefaultValues();
        $newRule->fill($defaultValues);
        //Je suis obligé de sauver 2 fois...sinon pas instancié et data est inconnu
        $newRule->forceSave();

        $this->vars['newRuleId'] = $newRule->id;

        //$this->autoSAve();

        return $this->renderRules();
    }


    public function onCreateShareRule()
    {
         if (!$id = post('share_rule_id')) {
            throw new ApplicationException('Error on share id');
        }

        $copyClass = $this->getRelationModel();
        $copyModel = $copyClass::find($id);


        //$this->restoreCacheRuleDataPayload();

        $newRule = $copyModel->replicate();
        $newRule->sort_order = null;
        $newRule->{$this->ruleMode.'eable_type'} = get_class($this->model);
        $newRule->{$this->ruleMode.'eable_id'} = $this->model->id;
        $newRule->getSubFormObject();
        $newRule->applyCustomData();
        $this->setCacheRuleData($newRule);
        $newRule->save();
        $this->vars['newRuleId'] = $newRule->id;
        return $this->renderRules();

        
    }

    public function getRuleRelation() {
        //return $this->model->{'rule_'.$this->ruleMode.'s'}();
        $fieldName = $this->formField->fieldName;
        return $this->model->{$fieldName}();
    }

    public function onDeleteRule()
    {
        $rule = $this->findRuleObj();

        if($this->autoSave) {
            $this->getRuleRelation()->remove($rule);
        } else {
            $this->getRuleRelation()->remove($rule, post('_session_key'));
        }
        return $this->renderRules();
    }

    public function onReorderUpRule()
    {
        $rule = $this->findRuleObj();
        $this->getNewOrderValue($rule, true);
        return $this->renderRules();
    }
    public function onReorderDownRule()
    {
        $rule = $this->findRuleObj();
        $this->getNewOrderValue($rule, false);
        return $this->renderRules();
    }

    public function getNewOrderValue($rule, $up = true) {
        $collection = $this->getRuleRelation()->get();
        if($up) {
            $collection = $collection->reverse();
        }
        $nextRule = false;
        foreach($collection as $testedRule) {
            if($nextRule) {
                $previousOrder = $rule->sort_order;
                $rule->sort_order = $testedRule->sort_order;
                $this->getRuleRelation()->save($rule, post('_session_key'));
                $testedRule->sort_order = $previousOrder;
                $this->getRuleRelation()->save($testedRule, post('_session_key'));
                return;
            }
            if($testedRule->id == $rule->id) {
                $nextRule = $testedRule;
            }
        }
        return $rule->sort_order;
    }

    public function onCancelRuleSettings()
    {
        $rule = $this->findRuleObj(post('new_rule_id'));
        $rule->delete();
        return $this->renderRules();
    }

    //
    // Postback deferring
    //
    public function getCacheRuleCode($rule)
    {
        return array_get($this->getCacheRuleData($rule), 'code') ?? 'ERROR';
    }

    public function getPartialBtn($rule) {
        if($pathBtn = $rule->getPartialPathBtns()) {
            return $this->makePartial($pathBtn, ['rule' => $rule]);
        } else {
            return null;
        }
    }

    public function getPartialComment($rule) {
        if($pathComment = $rule->getPartialPathComment()) {
            return $this->makePartial($pathComment, ['rule' => $rule]);
        } else {
            return null;
        }
    }

    // public function getCacheRuleAttributes($rule)
    // {
    //     //trace_log("getCacheRuleAttributes");
    //     return [];
    //     // $attributes = array_get($this->getCacheRuleData($rule), 'attributes');
    //     // $code = array_get($this->getCacheRuleData($rule), 'code');
    //     // $is_share = array_get($this->getCacheRuleData($rule), 'is_share');
    //     // $photos = array_get($this->getCacheRuleData($rule), 'photos');
    //     // $photo = array_get($this->getCacheRuleData($rule), 'photo');
    //     // //trace_log(array_merge($attributes, ["code" => $code], ["is_share" => $is_share]));
    //     // return array_merge($attributes, ["code" => $code], ["is_share" => $is_share]);
    // }

    // public function getCacheRuleTitle($rule)
    // {
    //     //trace_log("getCacheRuleTitle");
    //     return null;
    //     return array_get($this->getCacheRuleData($rule), 'title');
    // }

    // public function getCacheShareMode($rule)
    // {
    //     //trace_log("getCacheShareMode");
    //     return null;
    //     return array_get($this->getCacheRuleData($rule), 'share_mode');
    // }
    // public function getCacheMemo($rule)
    // {
    //     //trace_log("getCacheMemo");
    //     return null;
    //     //trace_log('memo : '.array_get($this->getCacheRuleData($rule), 'memo'));
    //     //trace_log($this->getCacheRuleData($rule));
    //     return array_get($this->getCacheRuleData($rule), 'memo');
    // }
    // public function getCacheRuleText($rule)
    // {
    //     //trace_log("getCacheRuleText");
    //     return null;
    //     $ruleText =  array_get($this->getCacheRuleData($rule), 'text');
    //     return $ruleText;
    // }
    // public function getCacheRuleData($rule, $default = null)
    // {
    //     //trace_log("getCacheRuleData");
    //     return null;
    //     $cache = post($this->getId().'rule_data', []);
    //     if (is_array($cache) && array_key_exists($rule->id, $cache)) {
    //         return json_decode($cache[$rule->id], true);
    //     }
    //     if ($default === false) {
    //         return null;
    //     }
    //     return $this->makeCacheRuleData($rule);
    // }
    // public function makeCacheRuleData($rule)
    // {
    //     //trace_log("makeCacheRuleData");
    //     //trace_log($rule->config_data);
    //     $data = [
    //         'attributes' => $rule->config_data,
    //         'title' => $rule->getTitle(),
    //         'memo' => $rule->getMemo(),
    //         'text' => $rule->getText(),
    //         'sort_order' => $rule->sort_order,
    //         'photo' => $rule->photo,
    //         'photos' => $rule->photos,
    //         'code' =>  $rule->code,
    //         'is_share' => $rule->is_share,
    //         'share_mode' =>  $rule->getShareMode(),
    //     ];
    //     return $data;
    // }

    public function setCacheCopyRuleData($rule)
    {
        $cache = post($this->getId().'rule_data', []);
        $cache[$rule->id] = json_encode($this->makeCacheRuleData($rule));
        Request::merge([
            $this->getId().'rule_data' => $cache
        ]);
    }

    public function setCacheRuleData($rule)
    {
        $cache = post($this->getId().'rule_data', []);
        $cache[$rule->id] = json_encode($this->makeCacheRuleData($rule));
        Request::merge([
            $this->getId().'rule_data' => $cache
        ]);
    }

    public function restoreCacheRuleDataPayload()
    {
        Request::merge([
            $this->getId().'rule_data' => json_decode(post($this->getId().'current_rule_data'), true)
        ]);
    }

    public function getCacheRuleDataPayload()
    {
        //trace_log("getCacheRuleDataPayload");
        return [];
        //return post($this->getId().'rule_data', []);
    }

    //
    // Helpers
    //

    /**
     * TODO utilser app Bind...
     */
    protected function getRuleClass() {
        if($this->ruleMode == 'content') {
           return  \Waka\Utils\Classes\Rules\RuleContentBase::class;
        } elseif($this->ruleMode == 'condition') {
            return \Waka\Utils\Classes\Rules\RuleConditionBase::class;
        }
        elseif($this->ruleMode == 'fnc') {
            return \Waka\Utils\Classes\Rules\FncBase::class;
        }
        elseif($this->ruleMode == 'ask') {
            return \Waka\Utils\Classes\Rules\AskBase::class;
        }
        elseif($this->ruleMode == 'bloc') {
            return \Waka\Utils\Classes\Rules\BlocBase::class;
        }
        elseif($this->ruleMode == 'action') {
            return \Waka\Babyler\Classes\Rules\RuleActionBase::class;
        }
        elseif($this->ruleMode == 'option') {
            return \Waka\Babyler\Classes\Rules\RuleOptionBase::class;
        }
    }

    protected function getAvailableTags()
    {
        if (!$rule = $this->findRuleObj(null, false)) {
            return null;
        }
        if(!$rule->showAttribute()) {
            return null;
        }
        

        $attributesObj = new \Waka\utils\Classes\Wattributes($this->model, $this->ruleMode.'s');
        $attributes = $attributesObj->getAttributes();

        //Cas des rules avec appel de fonctions
        $isFnc = $rule->getConfig('is_fnc');
        if(!$isFnc) {
            return $attributes;
        }
        $fnc_attributes = $rule->getConfig('fnc_attributes');
        $fncOutputs = $attributesObj->getManuelFncOutput($fnc_attributes, 'row');
        return  array_merge($fncOutputs, $attributes);
    }

    /**
     * Updates the primary rule rules container & send event
     * TODO remplacer rulemode par l'attribut mais je ne trouve pas
     * @return array
     */
    protected function renderRules($type = null)
    {
        $this->prepareVars();
        $result = [];
        if($this->splitRules) {
            $result = ['#'.$this->getId() => $this->makePartial('rules_splited')];
        } else {
            $result = ['#'.$this->getId() => $this->makePartial('rules')];
        }
        
        if(!$type) {
            return $result;
        }
        //TODO TROUVER UN MOYEN DE LE FAIRE DORECTME DANS BABYLOER
        if(method_exists($this->controller->asExtension('BabylerBehavior'), 'ruleBuilderExtendRefreshResults')) {
            $eventResult = $this->controller->asExtension('BabylerBehavior')->ruleBuilderExtendRefreshResults($type, $this->formField->fieldName);
            if ($eventResult) {
                $result = $eventResult + $result;
            }
        } else {
        }
        return $result;
    }

    protected function makeRuleFormWidget()
    {
        if ($this->ruleFormWidget !== null) {
            return $this->ruleFormWidget;
        }
        if (!$model = $this->findRuleObj($this->mode, false)) {
            return null;
        }
        if (!$model->hasFieldConfig()) {
            return null;
        }
        $config = $model->getFieldConfig($this->isRestrictedMode());
        $config->model = $model;
        $config->alias = $this->alias . 'Form';
        $config->arrayName = 'Rule';
        //
        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        //
        return $this->ruleFormWidget = $widget;
    }

    protected function getRules()
    {
        if ($this->rulesCache !== false) {
            return $this->rulesCache;
        }
        $relationObject = $this->getRelationObject();
        $rules = $relationObject->withDeferred($this->sessionKey)->get()->sortby('sort_order');

        return $this->rulesCache = $rules ?: null;
    }

    protected function findRuleObj($ruleId = null, $throw = true)
    {
        $ruleId = $ruleId ? $ruleId : post('current_rule_id');
        $rule = null;
        if (strlen($ruleId)) {
            $rule = $this->getRelationModel()->find($ruleId);
        }
        if ($throw && !$rule) {
            throw new ApplicationException('Rule not found');
        }
        return $rule;
    }
}
