<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * SubForm Model
 */
class SubFormModel extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;

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
     * Extends this model with the ask class
     * @param  string $class Class name
     * @return boolean
     */
    public function applySubFormClass($class = null)
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

    public function applyCustomData()
    {
        //trace_log('applyCustomData');
        $this->setCustomData();
        $this->loadCustomData(false);
    }

    protected function loadCustomData()
    {
        $this->setRawAttributes((array) $this->getAttributes() + (array) $this->config_data, true);
    }

    public function decryptConfigJsonData($config) {
        if (!$subFormObj = $this->getSubFormObject()) {
            throw new SystemException(sprintf('Unable to find subform object [%s]', $this->getSubFormClass()));
        }
        foreach($subFormObj->jsonable as $jsonKey) {
            if($config[$jsonKey] ?? false) {
                $config[$jsonKey] = json_decode($config[$jsonKey], true);
            }
        }
        return $config;
    }

    function isJson() {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function encryptConfigJsonData($config) {
        if (!$subFormObj = $this->getSubFormObject()) {
            throw new SystemException(sprintf('Unable to find subForm object [%s]', $this->getSubFormClass()));
        }
        foreach($subFormObj->jsonable as $jsonKey) {
            if($config[$jsonKey] ?? false) {
                $config[$jsonKey] = json_encode($config[$jsonKey], true);
            }
        }
        return $config;

    }

    protected function setCustomData()
    {
        if (!$subFormObj = $this->getSubFormObject()) {
            throw new SystemException(sprintf('Unable to find subForm object [%s]', $this->getSubFormClass()));
        }   
        
        /*
         * Spin over each field and add it to config_data
         */
        $config = $subFormObj->getFieldConfig();
        //
        /*
         * SubForm class has no fields
         */
        if (!isset($config->fields)) {
            return;
        }

        $staticAttributes = $this->staticAttributes;
        $realFields = $this->realFields;

        $fieldInConfig = array_diff(array_keys($config->fields), $realFields);

        $fieldAttributes = array_merge($staticAttributes, $fieldInConfig);

        $dynamicAttributes = array_only($this->getAttributes(), $fieldAttributes);

        //trace_log($dynamicAttributes);
        //TRICKY ! Gestion du problème des json. les champs json sont déjà transformé en json et le champs config va l'être aussi. donc je le decrypt juste avant l'enregistrement
        $dynamicAttributes = $this->decryptConfigJsonData($dynamicAttributes);

        $this->config_data = $dynamicAttributes;

        $this->setRawAttributes(array_except($this->getAttributes(), $fieldAttributes));
        
    }

    public function afterFetch()
    {
        //trace_log('afterFetch');
        $this->applySubFormClass();
        //TRICKY ! Gestion du problème des json. puisque les champs json vont être transfromé en array, je forcer l'encrypt juste avant de montrer le champs
        $this->config_data = $this->encryptConfigJsonData($this->config_data);
        //trace_log($this->config_data);
        $this->loadCustomData();
    }

    public function getText()
    {
        //Je ne comprend pas d' ou vient subForm text. Il empèche de retrouver le texte correctement
        // if (strlen($this->subForm_text)) {
        //     return $this->subForm_text;
        // }

        if ($subFormObj = $this->getSubFormObject()) {
            return $subFormObj->getText();
        }
    }

    public function getSubFormObject()
    {
        $this->applySubFormClass();

        return $this->asExtension($this->getSubFormClass());
    }

    public function getSubFormClass()
    {
        return $this->class_name;
    }
}
