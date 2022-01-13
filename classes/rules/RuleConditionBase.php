<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\ExtensionBase;
use Waka\Utils\Classes\DataSource;

/**
 * Notification rule base class
 *
 * @package waka\utils
 * @author Alexey Bobkov, Samuel Georges
 */
class RuleConditionBase extends SubForm
{
    private $error;
    protected $morphName;                              
    /**
     * Returns information about this rule, including name and description.
     */
    public function __construct($host = null)
    {
        $this->morphName = 'ruleeable';
        /*
         * Paths
         */
        //trace_log($this);
        $this->viewPath = $this->configPath = $this->guessConfigPathFrom($this);
        /*
         * Parse the config, if available
         */
        if ($formFields = $this->defineFormFields()) {
            $baseConfig = \Yaml::parseFile(plugins_path('/waka/utils/models/rules/fields_condition.yaml'));
            if(!$this->getEditableOption()) {
                unset($baseConfig['fields']['ask_emit']);
            }
            if(!$this->getShareModeConfig()) {
                unset($baseConfig['fields']['is_share']);
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

    public function setError($error = null) {
        $errorName = $error ? $error : $this->getText()." non compatible";
        $this->error = $errorName;
    }

    public function getError() {
        return $this->error ? $this->error : 'Erreur condition non spécifié';
    }
    public function listOperators() {
        return [
            'where' => "Est égale à ",
            'whereNot' => "Est différent de",
            'wherein' => "Est dans ces valeurs",
            'whereNotIn' => "N'est pas dans ces valeurs",
        ];
    }
    
}
