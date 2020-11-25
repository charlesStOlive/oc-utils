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
        $dataSource = Config::get('waka.wconfig::workflow');
        if ($dataSource) {
            return Yaml::parseFile(plugins_path() . $dataSource);
        } else {
            return Yaml::parseFile(plugins_path() . '/waka/wconfig/config/workflow.yaml');
        }

    }

}
