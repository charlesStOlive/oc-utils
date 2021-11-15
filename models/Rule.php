<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * Rule Model
 */
class Rule extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_rules';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array The rules to be applied to the data.
     */
    public $rules = [];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['config_data'];

    /**
     * @var array Relations
     */
    public $morphTo = [
        'ruleeable' => [],
    ];

    

    public function triggerRule($params)
    {
        try {
            $this->getRuleObject()->triggerRule($params);
        }
        catch (Exception $ex) {
            // We could log the error here, for now we should suppress
            // any exceptions to let other rules proceed as normal
            //traceLog('Error with ' . $this->getRuleClass());
            //traceLog($ex);
        }
    }

    /**
     * Extends this model with the rule class
     * @param  string $class Class name
     * @return boolean
     */
    public function applyRuleClass($class = null)
    {
        if (!$class) {
            $class = $this->class_name;
        }

        if (!$class) {
            return false;
        }

        if (!$this->isClassExtendedWith($class)) {
            $this->extendClassWith($class);
        }

        $this->class_name = $class;
        return true;
    }

    public function beforeSave()
    {
        $this->setCustomData();
    }

    public function afterSave()
    {
        // Make sure that this record is removed from the DB after being removed from a rule
        $removedFromRule = $this->rule_ruleeable_id === null && $this->getOriginal('rule_ruleeable_id');
        if ($removedFromRule && !$this->notification_rule()->withDeferred(post('_session_key'))->exists()) {
            $this->delete();
        }
    }

    public function applyCustomData()
    {
        $this->setCustomData();
        $this->loadCustomData();
    }

    protected function loadCustomData()
    {
        $this->setRawAttributes((array) $this->getAttributes() + (array) $this->config_data, true);
    }

    protected function setCustomData()
    {
        if (!$ruleObj = $this->getRuleObject()) {
            throw new SystemException(sprintf('Unable to find rule object [%s]', $this->getRuleClass()));
        }

        /*
         * Spin over each field and add it to config_data
         */
        $config = $ruleObj->getFieldConfig();

        /*
         * Rule class has no fields
         */
        if (!isset($config->fields)) {
            return;
        }

        $staticAttributes = ['rule_text'];

        $fieldAttributes = array_merge($staticAttributes, array_keys($config->fields));

        $dynamicAttributes = array_only($this->getAttributes(), $fieldAttributes);

        $this->config_data = $dynamicAttributes;

        $this->setRawAttributes(array_except($this->getAttributes(), $fieldAttributes));
    }

    public function afterFetch()
    {
        $this->applyRuleClass();
        $this->loadCustomData();
    }

    public function getText()
    {
        //Je ne comprend pas d' ou vient rule text. Il empèche de retrouver le texte correctement
        // if (strlen($this->rule_text)) {
        //     return $this->rule_text;
        // }

        if ($ruleObj = $this->getRuleObject()) {
            return $ruleObj->getText();
        }
    }

    public function getRuleObject()
    {
        $this->applyRuleClass();

        return $this->asExtension($this->getRuleClass());
    }

    public function getRuleClass()
    {
        return $this->class_name;
    }
}
