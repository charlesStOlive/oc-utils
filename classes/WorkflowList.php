<?php namespace Waka\Utils\Classes;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*
use Config;
use Yaml;

class WorkflowList
{
    public static function getAll()
    {
        $result = self::getSrConfig();
        return self::getSrConfig();
    }
    // public static function get($workflowKey)
    // {
    //     $result = self::getSrConfig();
    //     return $result[$workflowKey];
    // }
    /**
     * GLOBAL
     */

    public static function getSrConfig()
    {
        $workflows = Config::get('wcli.wconfig::workflows');
        if ($workflows) {
            $workflowsArray = [];
            foreach ($workflows as $workflow) {
                $wk = Yaml::parseFile(plugins_path() . $workflow);
                $workflowsArray = array_merge($workflowsArray, $wk);
            }
            return $workflowsArray;

            //return Yaml::parseFile(plugins_path() . $dataSources[0]);
        } else {
            throw new \SystemException('UImpossible de trouver la config du workflow');
        }
    }
}
