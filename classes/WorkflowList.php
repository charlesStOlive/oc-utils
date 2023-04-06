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
        trace_log('getRegisteredWorkflows');
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

    public static function getCronAuto() {
        $workflows = \Config::get('workflow');
        $listWorkflow = [];
        foreach($workflows as $workflow) {
            $modelAssocied = $workflow['supports'];
            foreach($modelAssocied as $class) {
                $cronAutoValues = static::getCronAutoValues($workflow);
                if (!empty($cronAutoValues)) {
                    array_push($listWorkflow, [
                        'class' => $class,
                        'cron_auto' => static::getCronAutoValues($workflow),
                    ]);
                }
            }

        }
        return $listWorkflow;
    }

    public static function getCronAutoValues(array $workflowConfig)
    {
        $results = [];

        $flattenedArray = \Arr::dot($workflowConfig['places']);

        foreach ($flattenedArray as $key => $value) {
            if (\Str::contains($key, '.metadata.cron_auto')) {
                $place = explode('.', $key)[0]; // Récupère le nom de la place
                $results[$place] = $value;
            }
        }

        return $results;
    }
}
