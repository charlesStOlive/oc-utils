<?php namespace Waka\Utils\Classes;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*
use Config;
use Yaml;
use System\Classes\PluginManager;

class WorkflowList
{
    public static function getAll()
    {
        return self::getRegisteredWorkflows();
    }
    // public static function get($workflowKey)
    // {
    //     $result = self::getSrConfig();
    //     return $result[$workflowKey];
    // }
    /**
     * GLOBAL
     */
    public static function getRegisteredWorkflows() {
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerWorkflows');
        if (!$bundles) {
            return [];
        }
        $workflowsArray = [];
        $workflows = call_user_func_array('array_merge',array_values($bundles));
        //trace_log($workflows);
        foreach ($workflows as $workflow) {
            $wk = Yaml::parseFile(plugins_path() . $workflow);
            $workflowsArray = array_merge($workflowsArray, $wk);
        }
        return $workflowsArray;
    }

    // public static function getSrConfig()
    // {
    //     $workflows = Config::get('wcli.wconfig::workflows');
    //     if ($workflows) {
    //         $workflowsArray = [];
    //         foreach ($workflows as $workflow) {
    //             $wk = Yaml::parseFile(plugins_path() . $workflow);
    //             $workflowsArray = array_merge($workflowsArray, $wk);
    //         }
    //         return $workflowsArray;

    //         //return Yaml::parseFile(plugins_path() . $dataSources[0]);
    //     } else {
    //         throw new \SystemException('UImpossible de trouver la config du workflow');
    //     }
    // }
}
