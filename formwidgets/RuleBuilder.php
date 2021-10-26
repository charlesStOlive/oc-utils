<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use Waka\Utils\Classes\Rules\RuleBase;
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

    protected $type = 'rules';

    protected $ruleMode = null;

    protected $targetClass = null;

    protected $full_access = 'noBody';

    public $restrictedMode = true;

    public $label = "waka.utils::lang.rules.label";
    public $prompt = "waka.utils::lang.rules.prompt";

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->fillFromConfig([
            'targetClass',
            'full_access',
            'ruleMode',
            'label',
            'prompt',
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
        $this->addCss('../../../../../wcli/wconfig/assets/css/formwidgets/rules.css');
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('rules_container');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['label'] = $this->label;
        $this->vars['prompt'] = $this->prompt;
        $this->vars['ruleMode'] = $this->ruleMode;
        $this->vars['formModel'] = $this->model;
        $this->vars['rules'] = $this->getRules();
        $this->vars['isRestrictedMode'] = $this->isRestrictedMode();
        //trace_log($this->getRules());
        $this->vars['ruleFormWidget'] = $this->ruleFormWidget;
        //$this->vars['attributesArray'] = $this->getAvailableTags();
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
        $cache = $this->getCacheRuleDataPayload();

        foreach ($cache as $id => $data) {
            $rule = $this->findRuleObj($id);

            if ($attributes = $this->getCacheRuleAttributes($rule)) {
                $rule->fill($attributes);
            }

            $rule->save(null, $this->sessionKey);
        }
    }

    //
    // AJAX
    //

    public function onLoadCreateRuleForm()
    {
        try {
            $rules = RuleBase::findRules($this->ruleMode, $this->targetClass);
            $this->vars['rules'] = $rules;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('create_rule_form');
    }

    public function restoreRestrictedField($rule) {
        if(!$this->isRestrictedMode()) {
            return [];
        }
        $oldDatas = $this->getCacheRuleAttributes($rule);
        //trace_log("oldDatas to restore");
        //trace_log($oldDatas);
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
        // trace_log("On Save ASK");
       

        $this->restoreCacheRuleDataPayload();

        $rule = $this->findRuleObj();

        $oldData = $this->restoreRestrictedField($rule);

        $data = post('Rule', []);
        //trace_log("posted data");
        //trace_log($data);
        $data = array_merge($data, $oldData);
        //trace_log("Data to save");
        //trace_log($data);

        $rule->fill($data);
        $rule->validate();
    
        $rule->rule_text = $rule->getRuleObject()->getText();

        $rule->applyCustomData();

        $this->setCacheRuleData($rule);

        return $this->renderRules($rule);
    }

    public function onLoadRuleSetup()
    {
        try {
            $rule = $this->findRuleObj();

            $data = $this->getCacheRuleAttributes($rule);
            //trace_log("onLoadRuleSetup dataCache");
            //trace_log($data);

            $this->ruleFormWidget->setFormValues($data);

            $this->prepareVars();
            $this->vars['rule'] = $rule;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('rule_settings_form');
    }

    public function onCreateRule()
    {
        if (!$className = post('rule_class')) {
            throw new ApplicationException('Please specify an rule');
        }

        $this->restoreCacheRuleDataPayload();

        $newRule = $this->getRelationModel();
        $newRule->mode = $this->mode;
        $newRule->{$this->ruleMode.'eable_type'} = get_class($this->model);
        $newRule->{$this->ruleMode.'eable_id'} = $this->model->id;
        $newRule->class_name = $className;
        $newRule->save();

        $this->getRuleRelation()->add($newRule, post('_session_key'));

        $this->vars['newRuleId'] = $newRule->id;

        return $this->renderRules();
    }

    public function getRuleRelation() {
        return $this->model->{'rule_'.$this->ruleMode.'s'}();
    }

    public function onDeleteRule()
    {
        $rule = $this->findRuleObj();

        $this->getRuleRelation()->remove($rule, post('_session_key'));

        return $this->renderRules();
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
    public function getCacheRuleCode($fnc)
    {
        return array_get($this->getCacheRuleData($fnc), 'attributes')['code'] ?? 'ERROR';
    }

    public function getCacheRuleAttributes($rule)
    {
        return array_get($this->getCacheRuleData($rule), 'attributes');
    }

    public function getCacheRuleTitle($rule)
    {
        return array_get($this->getCacheRuleData($rule), 'title');
    }

    public function getCacheRuleText($rule)
    {
        //trace_log('getCacheRuleText---');
        //trace_log($this->getCacheRuleData($rule));
        $ruleText =  array_get($this->getCacheRuleData($rule), 'text');
        //trace_log("actopn text : ".$ruleText);
        return $ruleText;
    }

    public function getCacheRuleData($rule, $default = null)
    {
        $cache = post('rule_data', []);

        if (is_array($cache) && array_key_exists($rule->id, $cache)) {
            return json_decode($cache[$rule->id], true);
        }

        if ($default === false) {
            return null;
        }

        return $this->makeCacheRuleData($rule);
    }

    public function makeCacheRuleData($rule)
    {
        //trace_log('makeCacheRuleData');
        
        $data = [
            'attributes' => $rule->config_data,
            'title' => $rule->getTitle(),
            'text' => $rule->getText(),
        ];


        //trace_log($data);

        return $data;
    }

    public function setCacheRuleData($rule)
    {
        $cache = post('rule_data', []);

        //trace_log($cache);

        $cache[$rule->id] = json_encode($this->makeCacheRuleData($rule));

        Request::merge([
            'rule_data' => $cache
        ]);
    }

    public function restoreCacheRuleDataPayload()
    {
        Request::merge([
            'rule_data' => json_decode(post('current_rule_data'), true)
        ]);
    }

    public function getCacheRuleDataPayload()
    {
        return post('rule_data', []);
    }

    //
    // Helpers
    //

    protected function getAvailableTags()
    {
        if (!$rule = $this->findRuleObj(null, false)) {
            return null;
        }
        if(!$rule->showAttribute()) {
            return null;
        }
        $attributes = new \Waka\utils\Classes\Wattributes($this->model, $this->type);
        return  $attributes->getAttributes();
    }

    /**
     * Updates the primary rule rules container
     * @return array
     */
    protected function renderRules()
    {
        $this->prepareVars();

        return [
            '#'.$this->getId() => $this->makePartial('rules')
        ];
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

        //trace_log('makeRuleFormWidget----------------');
        //trace_log($this->isRestrictedMode());

        $config = $model->getFieldConfig($this->isRestrictedMode());
        $config->model = $model;
        $config->alias = $this->alias . 'Form';
        $config->arrayName = 'Rule';

        $widget = $this->makeWidget('Backend\Widgets\Form', $config);

        return $this->ruleFormWidget = $widget;
    }

    protected function getRules()
    {
        if ($this->rulesCache !== false) {
            return $this->rulesCache;
        }
        

        $relationObject = $this->getRelationObject();
        $rules = $relationObject->withDeferred($this->sessionKey)->get();

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
