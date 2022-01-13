<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\ExtensionBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;

/**
 * Notification fnc base class
 *
 * @package waka\utils
 * @author Alexey Bobkov, Samuel Georges
 */
class FncBase extends SubForm 
{
    protected $morphName; 

    public function __construct($host = null)
    {
        $this->morphName = 'fnceable';
        /*
         * Paths
         */
        $this->viewPath = $this->configPath = $this->guessConfigPathFrom($this);;

        /*
         * Parse the config, if available
         */
        if ($formFields = $this->defineFormFields()) {
            $baseConfig = \Yaml::parseFile(plugins_path('/waka/utils/models/rules/fields_fnc.yaml'));
            if(!$this->getEditableOption()) {
                unset($baseConfig['fields']['ask_emit']);
            }
            if(!$this->getShareModeConfig()) {
                unset($baseConfig['fields']['is_share']);
            }
            $subformConfig = \Yaml::parseFile($this->configPath.'/'.$formFields);
            $mergeConfig = array_merge_recursive($baseConfig, $subformConfig);
            $this->fieldConfig = $this->makeConfig($mergeConfig);
        }

        if (!$this->host = $host) {
            return;
        }

        $this->boot($host);
    }

    public function isCodeInBridge($code) 
    {
        if($this->fncBridges()['all'] ?? false) {
            return true;
        } else {
            return array_key_exists($code, $this->fncBridges());
        }
        
    }

    public function getBridge($code) 
    {
            return $this->fncBridges()[$code];
    }

    public function getBridgeQuery($modelSrc, $code) 
    {
        $bridge = $this->getBridge($code);
        $relation = $bridge['relation'] ?? false;
        if(!$relation) {
            return $modelSrc;
        }
        if($relation == 'wakaAll') {
            return 'wakaAll';
        }
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

    public function listCalculConfig() {
        return [
            '=' => "égale à",
            '>' => "supérieur à",
            '<' => 'inférieur à',
            '>=' => "supérieur ou égale à",
            '<=' => "inférieur ou égale à",
        ];
    }

    function dynamic_comparison ($var1, $op, $var2) {
        switch ($op) {
            case "=":  return $var1 == $var2;
            case "!=": return $var1 != $var2;
            case ">=": return $var1 >= $var2;
            case "<=": return $var1 <= $var2;
            case ">":  return $var1 >  $var2;
            case "<":  return $var1 <  $var2;
            default:       return true;
        }   
    }

    public function getOutputs() {
        //trace_log($this->fieldConfig->outputs);
        return $this->fieldConfig->outputs ?? [];
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
                    \Log::error($conditionClass[0]. " n'existe pas dans le register asks du ".$plugin);
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
