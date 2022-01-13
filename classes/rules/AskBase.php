<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\ExtensionBase;
use Waka\Utils\Classes\DataSource;


/**
 * Notification ask base class
 *
 * @package waka\utils
 * @author Alexey Bobkov, Samuel Georges
 */
class AskBase extends SubForm
{
    protected $morphName;                              

    /**
     * Constructeur 
     */

    public function __construct($host = null)
    {
        $this->morphName = 'askeable';
        $this->viewPath = $this->configPath = $this->guessConfigPathFrom($this);

        /*
         * Parse the config, if available
         */
        if ($formFields = $this->defineFormFields()) {
            $baseConfig = \Yaml::parseFile(plugins_path('/waka/utils/models/rules/fields_ask.yaml'));
            if(!$this->getEditableOption()) {
                unset($baseConfig['fields']['ask_emit']);
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

    /**
     * Fonction unisque sur ASK
     */

    public function getWordType()
    {
        return array_get($this->subFormDetails(), 'outputs.word_type');
    }



    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {
        return 'resolve is missing in '.$this->getSubFormName();
    }


    /**
     * FONCTION INSTANCIATION DU SUBFORM ASK
     */

    /**
     * Spins over types registered in plugin base class with `registerSubFormRules`.
     * @return array
     */
    public static function findAsks($targetProductor = null)
    {
        $results = [];
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerWakaRules');

        foreach ($bundles as $plugin => $bundle) {
            foreach ((array) array_get($bundle, 'asks', []) as $conditionClass) {
                //trace_log($conditionClass[0]);
                $class = $conditionClass[0];
                $onlyProductors = $conditionClass['onlyProductors'] ?? [];

                if (!class_exists($class)) {
                    \Log::error($conditionClass[0]. " n'existe pas dans le register subforms du ".$plugin);
                    continue;
                }
                if (!in_array($targetProductor, $onlyProductors) && $onlyProductors != [] && $targetProductor != null) {
                    continue;
                }
                $obj = new $class;
                $results[$class] = $obj;
            }
        }

        return $results;
    }
}
