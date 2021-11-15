<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * RuleFnc Model
 */
class RuleFnc extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_rule_fncs';

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
        'fnceable' => [],
    ];

    public function triggerFnc($params)
    {
        try {
            $this->getFncObject()->triggerFnc($params);
        }
        catch (Exception $ex) {
            // We could log the error here, for now we should suppress
            // any exceptions to let other fncs proceed as normal
            //traceLog('Error with ' . $this->getFncClass());
            //traceLog($ex);
        }
    }

    /**
     * Extends this model with the fnc class
     * @param  string $class Class name
     * @return boolean
     */
    public function applyFncClass($class = null)
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
        //"La partie ci dessous est à retravailler. elle ne fonctionnait pas avant semble t il et encore moins avec les modifs pour fncs;
        \Log::error('voir 91 todo  fichier rule fnc');
        $removedFromRule = $this->rule_host_id === null && $this->getOriginal('rule_host_id');
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
        //trace_log('Set CUSTOM DATA');
        if (!$fncObj = $this->getFncObject()) {
            //trace_log('Set CUSTOM DATA problem');
            throw new SystemException(sprintf('Unable to find fnc object [%s]', $this->getFncClass()));
        }
        //trace_log('next');

        /*
         * Spin over each field and add it to config_data
         */
        $config = $fncObj->getFieldConfig();

        /*
         * Fnc class has no fields
         */
        if (!isset($config->fields)) {
            return;
        }

        $staticAttributes = ['fnc_text'];

        $fieldAttributes = array_merge($staticAttributes, array_keys($config->fields));

        $dynamicAttributes = array_only($this->getAttributes(), $fieldAttributes);

        $this->config_data = $dynamicAttributes;

        $this->setRawAttributes(array_except($this->getAttributes(), $fieldAttributes));
    }

    public function afterFetch()
    {
        $this->applyFncClass();
        $this->loadCustomData();
    }

    public function getText()
    {
        //Je ne comprend pas d' ou vient fnc text. Il empèche de retrouver le texte correctement
        // if (strlen($this->fnc_text)) {
        //     return $this->fnc_text;
        // }

        if ($fncObj = $this->getFncObject()) {
            return $fncObj->getText();
        }
    }

    public function getFncObject()
    {
        $this->applyFncClass();

        if($this->getFncClass()) {
            return $this->asExtension($this->getFncClass());
        }

        
    }

    public function getFncClass()
    {
        return $this->class_name;
    }
}
