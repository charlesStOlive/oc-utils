<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\ExtensionBase;
use Waka\Utils\Classes\DataSource;
use Waka\Utils\Interfaces\Ask as AskInterface;

/**
 * Notification ask base class
 *
 * @package waka\utils
 * @author Alexey Bobkov, Samuel Georges
 */
class AskBase extends ExtensionBase implements AskInterface
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

    /**
     * Returns information about this ask, including name and description.
     */
    public function askDetails()
    {
        return [
            'name'        => 'Ask',
            'description' => 'Ask description',
            'icon'        => 'icon-dot-circle-o',
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
            $baseConfig = \Yaml::parseFile(plugins_path('/waka/utils/models/rules/fields_ask.yaml'));
            if(!$this->getEditableOption()) {
                unset($baseConfig['fields']['ask_emit']);
            }
            $askConfig = \Yaml::parseFile($this->configPath.'/'.$formFields);
            $mergeConfig = array_merge_recursive($baseConfig, $askConfig);
            $this->fieldConfig = $this->makeConfig($mergeConfig);
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

    public function triggerAsk($params)
    {
    }

    public function getModel() {
        return $this->host->askeable;
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

    public function getTitle()
    {
        return $this->getAskName();
    }

    public function getText()
    {
        //trace_log('getText dans ask base');
        return $this->getAskDescription();
    }

    public function getCode()
    {
        //trace_log('getText dans ask base');
        return $this->host->config_data['code'] ?? 'En attente';
    }

    public function isEditable()
    {
        //trace_log('getText dans ask base');
        return $this->host->config_data['ask_emit'] ?? false;
    }
    public function getEditableOption()
    {
        return array_get($this->askDetails(), 'ask_emit');
    }

    public function getKeyValue()
    {
        return $this->getAskKeyValue();
    }

    public function getAskName()
    {
        return array_get($this->askDetails(), 'name');
    }

    public function getAskDescription()
    {
        return array_get($this->askDetails(), 'description');
    }

    public function getAskIcon()
    {
        return array_get($this->askDetails(), 'icon', 'icon-dot-circle-o');
    }

    

    /**
     * Extra field configuration for the condition.
     */
    public function defineFormFields()
    {
        return 'fields.yaml';
    }

    /**
     * Determines if this ask uses form fields.
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
        return 'resolve is missing in '.$this->getAskName();
    }

    /**
     * Spins over types registered in plugin base class with `registerAskRules`.
     * @return array
     */
    public static function findAsks($targetClass = null)
    {
        $results = [];
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerWakaRules');

        foreach ($bundles as $plugin => $bundle) {
            foreach ((array) array_get($bundle, 'asks', []) as $conditionClass) {
                $class = $conditionClass[0];
                $classType = $conditionClass['only'] ?? [];
                if (!class_exists($class)) {
                    continue;
                }
                if (!in_array($targetClass, $classType) && $classType != [] && $targetClass != null) {
                    trace_log('merde');
                    continue;
                }
                $obj = new $class;
                $results[$class] = $obj;
            }
        }

        return $results;
    }
}
