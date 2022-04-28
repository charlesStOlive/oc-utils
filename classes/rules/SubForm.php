<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\Extendable;
use Waka\Utils\Classes\DataSource;
use \System\Classes\MediaLibrary;
use System\Classes\ImageResizer;

/**
 * Notification subform base class
 *
 * @package waka\utils
 * @author Charles Sainto
 */
class SubForm extends Extendable
{
    use \System\Traits\ConfigMaker;
    use \System\Traits\ViewMaker;
    use \Winter\Storm\Extension\ExtensionTrait;
    //use \Winter\Storm\Extension\ExtendableTrait;

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
        //trace_log("boot");
        if (!$host->exists) {
            $this->initConfigData($host);
        }
        foreach($this->jsonable as $json) {
            $host->addJsonable($this->jsonable);
        }
        // Apply validation rules
        $host->rules = array_merge($host->rules, $this->defineValidationRules());
    }
    /**
     * INITIALISATION
     * vars $fields adresse du fichier de config de base;
     */
    protected function init($baseFields) {
        $this->viewPath = $this->configPath = array_get($this->subFormDetails(), 'forcedConfigPath');
        if(!$this->viewPath) $this->viewPath = $this->configPath = $this->guessConfigPathFrom($this);
        /*
         * Parse the config, if available
         */
        if ($formFields = $this->defineFormFields()) {
            $baseConfig = \Yaml::parseFile(plugins_path($baseFields));
            if(!$this->getEditableOption()) {
                unset($baseConfig['fields']['subform_emit']);
            }
            if($mode = $this->getShareModeConfig()) {
                if($mode == 'choose') {
                    $shareConfig = [
                        'label' => "Mode de partage",
                        'type' => 'dropdown',
                        'options' => ['Pas de partage', 'Partage ressource', 'Partage complet'],
                        'span' => 'storm',
                        'cssClass' =>  'col-xs-4',
                    ];
                    $baseConfig['fields']['is_share'] = $shareConfig;
                } else {
                    //On laisse le champs classique
                }   
            } else {
                 unset($baseConfig['fields']['is_share']);
            }
            $askConfig = \Yaml::parseFile($this->configPath.'/'.$formFields);
            $mergeConfig = array_merge_recursive($baseConfig, $askConfig);
            $this->fieldConfig = $this->makeConfig($mergeConfig);
        }
    }

    public function triggerSubForm($params)
    {
    }

    public function defaultImportExportConfig() {
        return [];
    }
    public function getimportExportConfig() {
        $wakaExport = array_get($this->subFormDetails(), 'wakaExport');
        if($wakaExport) {
            return array_merge($this->defaultImportExportConfig(), $wakaExport);
        } else {
            return $this->defaultImportExportConfig();
        }  
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
        if($model->data_source) {
            return \DataSources::find($model->data_source);
        } else {
            return null;
        }  
    }
    

    public function getDefaultValues() {
        $formFields = $this->defineFormFields();
        $askConfig = \Yaml::parseFile($this->configPath.'/'.$formFields);
        return $this->getRecursiveDefaultValues($askConfig);

    }
    public function getRecursiveDefaultValues(array $fields) {
        if($fields['tabs'] ?? false) {
            $fields = $fields['tabs']['fields'];
        } elseif($fields['fields'] ?? false) {
            $fields = $fields['fields'];
        }
        $defaultValues = [];
        foreach($fields as $key=>$field) {
            // if($key = 'tabs') {
            //     $defaultValues = $this->getRecursiveDefaultValues($field);
            // }
            if($subField = $field['tabs'] ?? false) {
                $defaultValues[$key] =  $this->getRecursiveDefaultValues($subField);
            } 
            else if($subField = $field['form']['fields'] ?? false) {
                $fieldType = $field['type'] ?? null;
                if($fieldType == 'repeater') {
                    //trace_log('c est  un repeater');
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
            } else if(!is_array($data)) {
                return json_decode($data, true);
            } else {
                return $data;
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
                } else if(!is_array($data)) {
                    $returnDatas[$key] = json_decode($data, true);
                } else {
                    $returnDatas[$key] = $data;
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
        //trace_log(array_get($this->subFormDetails(), 'subform_emit'));
        return array_get($this->subFormDetails(), 'subform_emit');
    }
    public function getMemo()
    {
        //trace_log('getText dans subform base');
        return $this->host->config_data['memo'] ?? null;
    }
    public function getPartialPathBtns()
    {
        //trace_log('getText dans subform base');
       $partialName =  array_get($this->subFormDetails(), 'partials.btns');
       if($partialName) {
           return $this->viewPath.'/'.$partialName;
       } else {
           return null;
       }
    }

    public function getPartialPathComment()
    {
        //trace_log('getText dans subform base');
       $partialName =  array_get($this->subFormDetails(), 'partials.comment');
       if($partialName) {
           return $this->viewPath.'/'.$partialName;
       } else {
           return null;
       }
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
        $baseAttributes = array_get($this->subFormDetails(), 'show_attributes');
        // $isFnc = $this->getConfig('is_fnc');
        // $addAttributes = $this->getConfig('fnc_name');

        return $baseAttributes;
    }
    public function getShareModeConfig()
    {
        return array_get($this->subFormDetails(), 'share_mode');
    }
    public function getShareMode() {
        //trace_log('getShareMode');
        if(!$this->host->is_share) {
            return null;
        }
        $mode = $this->getShareModeConfig();
        if($mode == "choose") {
            if($this->host->is_share == 1) {
                return 'ressource';
            } else {
                return 'full';
            }
        } else {
            return $mode;
        } 
    }
    public function getType()
    {
        return array_get($this->subFormDetails(), 'type');
    }

    public function getWordType()
    {
        return array_get($this->subFormDetails(), 'outputs.word_type');
    }
    public function getOutputTypes($mode = null)
    {
        $outputs  = array_get($this->subFormDetails(), 'outputs');
        if($this->getConfig('is_fnc')) {
            return 'reTwig';

        }
        if(!is_iterable($outputs)) {
            return $outputs;

        } 
        if(!is_iterable($outputs) && $mode) {
            return $outputs[$model] ?? null;
        }  else {
            return null;
        }
    }

    public function isFnc() {
        return $this->getConfig('is_fnc');
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
    /**
     * UNIQUEMENT POUR RULE pour ASK et FNC on utilise findAsk et findFnc qui sont dans leur prore classes.
     */
    public static function findRules($mode, $targetClass = null, $dataSourceCode = null)
    {
        //trace_log('find rules');
        //trace_log($mode);
        $results = [];
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerWakaRules');
        $mode = $mode.'s';
        foreach ($bundles as $plugin => $bundle) {
            foreach ((array) array_get($bundle, $mode, []) as $conditionClass) {
                //trace_log($conditionClass[0]);
                $class = $conditionClass[0];
                $onlyClass = $conditionClass['onlyClass'] ?? [];
                $excludeClass = $conditionClass['excludeClass'] ?? [];
                if (!class_exists($class)) {
                    \Log::error($conditionClass[0]. " n'existe pas dans le register rules du ".$plugin);
                    continue;
                }
                if (!in_array($targetClass, $onlyClass) && $onlyClass != [] && $targetClass != null) {
                    //trace_log('merde');
                    continue;
                }
                if (in_array($targetClass, $excludeClass) && $excludeClass != [] && $targetClass != null) {
                    //trace_log('merde');
                    continue;
                }
                $obj = new $class;
                if($mode == 'fncs') {
                    //Dans les fnc on s interesse au code data source et on verifie si il est dans le bridge
                    //trace_log("dataSourceCode : ".$dataSourceCode);
                    if($obj->isCodeInBridge($dataSourceCode)) {
                    $results[$class] = $obj;
                    }
                } else {
                    $results[$class] = $obj;
                }
            }
        }
        return $results;
    }

    /**
     * CHERCHE LES COMPOSANTS PARATG2 DISPONIBLE
     */
    public static function findShares($mode, $model, $dataSource)
    {
        //trace_log('----ressource = '.$dataSource);
        $modelClass = get_class($model);
        $ruleModel = $model->{'rule_'.$mode.'s'}()->getRelated();
        $components = $ruleModel->where('is_share','<>', null)->get();
        //trace_log($components->pluck('code'));
        //Impossible de bosser avec each ou reject de Collection. je ne sais pas pourquoi...je crée donc une autre collection et je push les bons résultats.
        //je bosse en collection pour garder des valeurs unique dimplement à la fin. 
        $finalRules = new \Winter\Storm\Support\Collection();
        foreach($components as $component) {
            //trace_log($component->code);
            //trace_log($component->getShareModeConfig());
            if($component->getShareMode() == 'full') {
                //trace_log($component->code);
                $finalRules->push($component);
            } else if ($component->getShareMode() == 'ressource') {
                if($dataSource == $component->getDs()->code) {
                    $finalRules->push($component);
                }
            }
        }
        return $finalRules->unique('code');
    }

    public function filterFields($fields, $context = null) {
        return null;
    }

    
    // private function wakaExport($exporterObject, $subdirectory = null) {
    //     return 
    // }



}
