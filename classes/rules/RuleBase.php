<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\ExtensionBase;
use Waka\Utils\Classes\DataSource;
use Waka\Utils\Interfaces\Rule as RuleInterface;

/**
 * Notification rule base class
 *
 * @package waka\utils
 * @author Alexey Bobkov, Samuel Georges
 */
class RuleBase extends ExtensionBase implements RuleInterface
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

    /**
     * Returns information about this rule, including name and description.
     */
    public function ruleDetails()
    {
        return [
            'name'        => 'Rule',
            'description' => 'Rule description',
            'icon'        => 'icon-dot-circle-o',
            'show_attributes' => true,
        ];
    }

    public function __construct($host = null)
    {
        /*
         * Paths
         */
        //trace_log($this);
        $this->viewPath = $this->configPath = $this->guessConfigPathFrom($this);
        //trace_log($this->viewPath);

        /*
         * Parse the config, if available
         */
        if ($formFields = $this->defineFormFields()) {
            // $baseConfig = \Yaml::parseFile(plugins_path('/waka/utils/models/rules/fields_rule.yaml'));
            // if(!$this->getEditableOption()) {
            //     unset($baseConfig['fields']['rule_emit']);
            // }
            $ruleConfig = \Yaml::parseFile($this->configPath.'/'.$formFields);
            //$mergeConfig = array_merge_recursive($baseConfig, $ruleConfig);
            //$this->fieldConfig = $this->makeConfig($mergeConfig);
            $this->fieldConfig = $this->makeConfig($ruleConfig);
        }

        if (!$this->host = $host) {
            return;
        }

        $this->boot($host);
    }

    /**
     * Boot method called when the condition class is first loaded
     * with an existing model.
     * @return array
     */
    public function boot($host)
    {
        // Set default data
        if (!$host->exists) {
            $this->initConfigData($host);
        }

        // Apply validation rules
        $host->rules = array_merge($host->rules, $this->defineValidationRules());
    }

    public function triggerRule($params)
    {
    }

    public function getModel() {
        return $this->host->ruleeable;
    }

    public function getDs() {
        $model = $this->getModel();
        if($model) {
            return \DataSources::find($model->data_source);
        } else {
            return null;
        }
        
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
                return explode(",",$data);
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
                    $returnDatas[$key] = explode(",", $data);
                }
                
            } else {
                $returnDatas[$key] = $data;
            }
        }
        return $returnDatas;
    }

    public function getTitle()
    {
        return $this->getRuleName();
    }

    public function getText()
    {
        //trace_log('getText dans rule base');
        return $this->getRuleDescription();
    }

    public function getCode()
    {
        //trace_log('getText dans rule base');
        return $this->host->config_data['code'] ?? 'En attente';
    }

    public function isEditable()
    {
        //trace_log('getText dans rule base');
        return $this->host->config_data['rule_emit'] ?? false;
    }
    public function getEditableOption()
    {
        return array_get($this->ruleDetails(), 'rule_emit');
    }

    public function getKeyValue()
    {
        return $this->getRuleKeyValue();
    }

    public function getRuleName()
    {
        return array_get($this->ruleDetails(), 'name');
    }

    public function getRuleDescription()
    {
        return array_get($this->ruleDetails(), 'description');
    }

    public function getRuleIcon()
    {
        return array_get($this->ruleDetails(), 'icon', 'icon-dot-circle-o');
    }

    public function showAttribute()
    {
        return array_get($this->ruleDetails(), 'show_attributes');
    }
    public function getWordType()
    {
        return array_get($this->ruleDetails(), 'word_type');
    }

    

    /**
     * Extra field configuration for the condition.
     */
    public function defineFormFields()
    {
        return 'fields.yaml';
    }

    /**
     * Determines if this rule uses form fields.
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

    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {
        return 'resolve is missing in '.$this->getRuleName();
    }

    /**
     * Spins over types registered in plugin base class with `registerRuleRules`.
     * @return array
     */
    public static function findRules($mode, $targetClass = null)
    {
        $results = [];
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerWakaRules');
        $mode = $mode.'s';
        foreach ($bundles as $plugin => $bundle) {
            foreach ((array) array_get($bundle, $mode, []) as $conditionClass) {
                //trace_log($conditionClass[0]);
                $class = $conditionClass[0];
                $classType = $conditionClass['only'] ?? [];
                if (!class_exists($class)) {
                    \Log::error($conditionClass[0]. " n'existe pas dans le register rules du ".$plugin);
                    continue;
                }
                if (!in_array($targetClass, $classType) && $classType != [] && $targetClass != null) {
                    //trace_log('merde');
                    continue;
                }
                $obj = new $class;
                $results[$class] = $obj;
            }
        }

        return $results;
    }
}
