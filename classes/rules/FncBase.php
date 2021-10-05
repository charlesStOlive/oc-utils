<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\ExtensionBase;
use Waka\Utils\Interfaces\Fnc as FncInterface;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;

/**
 * Notification fnc base class
 *
 * @package waka\utils
 * @author Alexey Bobkov, Samuel Georges
 */
class FncBase extends ExtensionBase 
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
     * Returns information about this fnc, including name and description.
     */
    public function fncDetails()
    {
        return [
            'name'        => 'Fnc',
            'description' => 'Fnc description',
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
            $baseConfig = \Yaml::parseFile(plugins_path('/waka/utils/models/rules/fields_fnc.yaml'));
            $fncConfig = \Yaml::parseFile($this->configPath.'/'.$formFields);
            $mergeConfig = array_merge_recursive($baseConfig, $fncConfig);
            $this->fieldConfig = $this->makeConfig($mergeConfig);
        }

        if (!$this->host = $host) {
            return;
        }

        $this->boot($host);
    }

    public function isCodeInBridge($code) 
    {
        return array_key_exists($code, $this->fncBridges());
    }

    public function getBridge($code) 
    {
            return $this->fncBridges()[$code];
    }

    public function getBridgeQuery($modelSrc, $code) 
    {
        $bridge = $this->getBridge($code);
        $relation = $bridge['relation'];
        $relationExploded = explode('.', $relation);
        foreach($relationExploded as $key=>$subrelation) {
            if ($key === array_key_last($relationExploded)) {
                $modelSrc = $modelSrc->{$subrelation}();
            } else {
                $modelSrc = $modelSrc->{$subrelation};
            }
        }
        return $modelSrc;
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

    public function triggerFnc($params)
    {
    }

    public function getModel() {
        return $this->host->fnceable;
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
        return $this->getFncName();
    }

    public function getText()
    {
        //trace_log('getText dans fnc base');
        return $this->getFncDescription();
    }

    public function getCode()
    {
        //trace_log('getText dans fnc base');
        return $this->host->config_data['code'] ?? 'En attente';
    }

    public function isEditable()
    {
        //trace_log('getText dans fnc base');
        return $this->host->config_data['fnc_emit'] ?? false;
    }
    public function getEditableOption()
    {
        return array_get($this->fncDetails(), 'fnc_emit');
    }

    public function getKeyValue()
    {
        return $this->getFncKeyValue();
    }

    public function getFncName()
    {
        return array_get($this->fncDetails(), 'name');
    }

    public function getFncDescription()
    {
        return array_get($this->fncDetails(), 'description');
    }

    public function getFncIcon()
    {
        return array_get($this->fncDetails(), 'icon', 'icon-dot-circle-o');
    }

    public function getOutputs() {
        //trace_log($this->fieldConfig->outputs);
        return $this->fieldConfig->outputs ?? [];
    }

    

    /**
     * Extra field configuration for the condition.
     */
    public function defineFormFields()
    {
        return 'fields.yaml';
    }

    /**
     * Determines if this fnc uses form fields.
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

    public function resolve($modelSrc, $poductorDs) {
        return 'resolve is missing in '.$this->getFncName();
    }

    /**
     * Spins over types registered in plugin base class with `registerFncRules`.
     * @return array
     */
    public static function findFncs($targetProductor, $dataSourceCode)
    {
        //trace_log($dataSourceCode);
        $results = [];
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerWakaRules');

        foreach ($bundles as $plugin => $bundle) {
            foreach ((array) array_get($bundle, 'fncs', []) as $conditionClass) {
                $class = $conditionClass[0];
                $onlyProductors = $conditionClass['onlyProductors'] ?? [];
                if (!class_exists($class)) {
                    \Log::error('la class : '.$class.' existe pas');
                    continue;
                }
                if (!in_array($targetProductor, $onlyProductors) && $onlyProductors != [] && $targetProductor != null) {
                    continue;
                }
                $obj = new $class;
                if($obj->isCodeInBridge($dataSourceCode)) {
                    $results[$class] = $obj;
                }
                
            }
        }

        return $results;
    }
}
