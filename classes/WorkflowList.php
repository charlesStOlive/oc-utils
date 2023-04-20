<?php namespace Waka\Utils\Classes;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*
use Config;
use Yaml;
use System\Classes\PluginManager;
use Cache;

class WorkflowList
{
    public static function getAll()
    {
       $cacheKey = 'allWorkflows';

        // Vérifie si les workflows sont en cache
        if (Cache::has($cacheKey)) {
            trace_log(Cache::get($cacheKey));
            return Cache::get($cacheKey);
        }

        // Récupère tous les workflows enregistrés
        $workflows = static::getRegisteredWorkflows();

        // Stocke les workflows en cache de manière permanente
        Cache::rememberForever($cacheKey, function () use ($workflows) {
            return $workflows;
        });

        return $workflows;
    }
    
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
