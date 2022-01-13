<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\ExtensionBase;
use Waka\Utils\Classes\DataSource;

/**
 * Notification subform base class
 *
 * @package waka\utils
 * @author Charles Sainto
 */
class SubForm extends ExtensionBase
{
    use \System\Traits\ConfigMaker;
    use \System\Traits\ViewMaker;

    /**
     * @var Model host object
     */
    protected $host;

    /**
     * @var mixed Extra field configuration for the condition.
     */
    protected $fieldConfig;

    public $parentClass = null;

    public $jsonable = [];


    // A spécifier dans la règle de base.                   
    protected $morphName;                              

    /**
     * Boot method called when the condition class is first loaded
     * with an existing model.
     * @return array
     */
    public function boot($host)
    {
        // Set default data
        trace_log("boot");
        if (!$host->exists) {
            $this->initConfigData($host);
        }
        foreach($this->jsonable as $json) {
            $host->addJsonable($this->jsonable);
        }
        

        // Apply validation rules
        $host->rules = array_merge($host->rules, $this->defineValidationRules());
    }

    public function triggerSubForm($params)
    {
    }

    public function getModel() {
        if(!$this->morphName) {
            throw new \SystemException('SubForm morhName nom definis dans le declarateur');
        }
        //trace_log('morphname : '.$this->morphName);
        return $this->host->{$this->morphName};
    }

    public function getDs() {
        $model = $this->getModel();
        if($model) {
            return \DataSources::find($model->data_source);
        } else {
            return null;
        }
        
    }

    public function getDefaultValues() {
        trace_log($this->fieldConfig->fields);
        return $this->getRecursiveDefaultValues($this->fieldConfig->fields);

    }
    public function getRecursiveDefaultValues(array $fields) {
        $defaultValues = [];
        foreach($fields as $key=>$field) {
            if($subField = $field['form']['fields'] ?? false) {
                $fieldType = $field['type'] ?? null;
                if($fieldType == 'repeater') {
                    trace_log('c est  un repeater');
                    $defaultValues[$key] =  [$this->getRecursiveDefaultValues($subField)];
                } else {
                    $defaultValues[$key] = $this->getRecursiveDefaultValues($subField);
                }
                
                
            } else {
                $defaultValue = $field['default'] ?? null;
                if($defaultValue) {
                    $defaultValues[$key] = $defaultValue;
                }
            }
        }
        return $defaultValues;

    }

    public function getClientModel($clientId) {
        $clientModel = $this->getDs()::find($clientId);
        if(!$clientModel) {
            throw new ApplicationException('Modèle non trouvé pour la résolution de la tache demandable.'); 
        } else {
            return $clientModel;
        }
        
    }

    public function getConfig($key)
    {
        $data = $this->host->config_data[$key] ?? null;
        if(in_array($key,$this->jsonable)) {
            if(!$data) {
                return [];
            } else {
                return json_decode($data, true);
            }
            
        } else {
            return $data;
        }
    }

    public function getConfigs()
    {
        $datas = $this->host->config_data ?? null;
        $returnDatas = [];
        foreach($datas as $key=>$data) {
            if(in_array($key,$this->jsonable)) {
                if(!$data) {
                    $returnDatas[$key] = [];
                } else {
                    $returnDatas[$key] = json_decode($data, true);
                }
                
            } else {
                $returnDatas[$key] = $data;
            }
        }
        return $returnDatas;
    }

    public function getTitle()
    {
        return $this->getSubFormName();
    }

    public function getText()
    {
        //trace_log('getText dans subform base');
        return $this->getSubFormDescription();
    }

    public function getCode()
    {
        //trace_log('getText dans subform base');
        return $this->host->code ?? 'En attente';
    }

    public function isEditable()
    {
        //trace_log('getText dans subform base');
        return $this->host->config_data['subform_emit'] ?? false;
    }
    public function getEditableOption()
    {
        return array_get($this->subFormDetails(), 'subform_emit');
    }

    public function getKeyValue()
    {
        return $this->getSubFormKeyValue();
    }

    public function getSubFormName()
    {
        return array_get($this->subFormDetails(), 'name');
    }

    public function getSubFormDescription()
    {
        return array_get($this->subFormDetails(), 'description');
    }

    public function getSubFormIcon()
    {
        return array_get($this->subFormDetails(), 'icon', 'icon-dot-circle-o');
    }

    public function showAttribute()
    {
        return array_get($this->subFormDetails(), 'show_attributes');
    }
    public function getWordType()
    {
        return array_get($this->subFormDetails(), 'outputs.word_type');
    }

    

    /**
     * Extra field configuration for the condition.
     */
    public function defineFormFields()
    {
        return 'fields.yaml';
    }

    /**
     * Determines if this subform uses form fields.
     * @return bool
     */
    public function hasFieldConfig()
    {
        return !!$this->fieldConfig;
    }

    /**
     * Returns the field configuration used by this model.
     */
    public function getFieldConfig($restrictedMode = false)
    {
        //trace_log('getFieldConfig restrictedMode : '.$restrictedMode);
        if(!$restrictedMode) {
            return $this->fieldConfig;
        }
        $fieldConfig = $this->fieldConfig->fields;
        foreach($fieldConfig as $key=>$field) {
            if($field['restricted'] ?? false) {
                unset($fieldConfig[$key]);
            }
        }
        $this->fieldConfig->fields = $fieldConfig;
        return $this->fieldConfig;
    }

    public function getRestrictedFields()
    {
        $restrictedConfig = [];
        $fieldConfig = $this->fieldConfig->fields;
        foreach($fieldConfig as $key=>$field) {
            if($field['restricted'] ?? false) {
                array_push($restrictedConfig, $key);
            }
        }
        return $restrictedConfig;
    }

    /**
     * Initializes configuration data when the condition is first created.
     * @param  Model $host
     */
    public function initConfigData($host) {}

    /**
     * Defines validation rules for the custom fields.
     * @return array
     */
    public function defineValidationRules()
    {
        return [];
    }

    /**
     * Méthode pour ajouter des attributs à un array.
     */
    public function getAttributesDs($model)
    {
        return $model->map(function ($item) {
            $atts = $item->attributesToDs;
            foreach ($atts as $att) {
                $item->append($att);
            }
            return $item;
        });
    }
}
