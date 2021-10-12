<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * RuleAsk Model
 */
class RuleAsk extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_rule_asks';

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
        'askeable' => [],
    ];

    public function triggerAsk($params)
    {
        try {
            $this->getAskObject()->triggerAsk($params);
        }
        catch (Exception $ex) {
            // We could log the error here, for now we should suppress
            // any exceptions to let other asks proceed as normal
            //traceLog('Error with ' . $this->getAskClass());
            //traceLog($ex);
        }
    }

    /**
     * Extends this model with the ask class
     * @param  string $class Class name
     * @return boolean
     */
    public function applyAskClass($class = null)
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
        $removedFromRule = $this->rule_askeable_id === null && $this->getOriginal('rule_askeable_id');
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
        if (!$askObj = $this->getAskObject()) {
            throw new SystemException(sprintf('Unable to find ask object [%s]', $this->getAskClass()));
        }

        /*
         * Spin over each field and add it to config_data
         */
        $config = $askObj->getFieldConfig();

        /*
         * Ask class has no fields
         */
        if (!isset($config->fields)) {
            return;
        }

        $staticAttributes = ['ask_text'];

        $fieldAttributes = array_merge($staticAttributes, array_keys($config->fields));

        $dynamicAttributes = array_only($this->getAttributes(), $fieldAttributes);

        $this->config_data = $dynamicAttributes;

        $this->setRawAttributes(array_except($this->getAttributes(), $fieldAttributes));
    }

    public function afterFetch()
    {
        $this->applyAskClass();
        $this->loadCustomData();
    }

    public function getText()
    {
        //Je ne comprend pas d' ou vient ask text. Il empèche de retrouver le texte correctement
        // if (strlen($this->ask_text)) {
        //     return $this->ask_text;
        // }

        if ($askObj = $this->getAskObject()) {
            return $askObj->getText();
        }
    }

    public function getAskObject()
    {
        $this->applyAskClass();

        return $this->asExtension($this->getAskClass());
    }

    public function getAskClass()
    {
        return $this->class_name;
    }
}
