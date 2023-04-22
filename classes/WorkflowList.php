<?php

namespace Waka\Utils\Classes;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*
use Config;
use Yaml;
use System\Classes\PluginManager;
use Cache;

class WorkflowList
{
    const CK_WORKFLOWS = 'allWorkflows';
    const CK_CRON_AUTO = 'cronAuto';

    public static function getAll()
    {
        // Vérifie si les workflows sont en cache
        if (Cache::has(self::CK_WORKFLOWS)) {
            return Cache::get(self::CK_WORKFLOWS);
        } else {
            //On va enlever le cache des cronAuto 
            \Cache::forget(self::CK_CRON_AUTO);
        }
        // Récupère tous les workflows enregistrés
        $workflows = static::getRegisteredWorkflows();

        // Stocke les workflows en cache de manière permanente
        Cache::rememberForever(self::CK_WORKFLOWS, function () use ($workflows) {
            return $workflows;
        });

        return $workflows;
    }

    public static function getRegisteredWorkflows()
    {
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerWorkflows');
        if (!$bundles) {
            return [];
        }
        $workflowsArray = [];
        $workflows = call_user_func_array('array_merge', array_values($bundles));
        //trace_log($workflows);
        foreach ($workflows as $workflow) {
            $wk = Yaml::parseFile(plugins_path() . $workflow);
            $workflowsArray = array_merge($workflowsArray, $wk);
        }
        return $workflowsArray;
    }

    public static function getCronAuto()
    {
        // Vérifie si les workflows sont en cache
        if (Cache::has(self::CK_CRON_AUTO)) {
            return Cache::get(self::CK_CRON_AUTO);
        }
        // Récupère tous les workflows enregistrés
        $cronAuto = static::getCronAutoFromWorkflows();
        // Stocke les workflows en cache de manière permanente
        Cache::rememberForever(self::CK_CRON_AUTO, function () use ($cronAuto) {
            return $cronAuto;
        });
        return $cronAuto;
    }

    private static function getCronAutoFromWorkflows()
    {

        $workflows = \Config::get('workflow');
        $listWorkflow = [];
        foreach ($workflows as $workflow) {
            $modelAssocied = $workflow['supports'];
            foreach ($modelAssocied as $class) {
                $cronAutoValues = static::getCronAutoValues($workflow);
                if (!empty($cronAutoValues)) {
                    array_push($listWorkflow, [
                        'class' => $class,
                        'cron_auto' => static::getCronAutoValues($workflow),
                    ]);
                }
            }
        }
        $listWorkflow = array_reduce($listWorkflow, function ($carry, $item) {
            $time = $item['cron_auto']['time'];

            if (!isset($carry[$time])) {
                $carry[$time] = [];
            }

            $carry[$time][] = $item;

            return $carry;
        }, []);

        return $listWorkflow;
    }


    private static function getCronAutoValues(array $workflowConfig)
    {
        $results = [];

        $flattenedArray = \Arr::dot($workflowConfig['places']);
        $defaultCronTime = $workflowConfig['metadata']['cron_auto_time'] ?? '00h04';

        foreach ($flattenedArray as $key => $value) {
            if (\Str::contains($key, '.metadata.cron_auto')) {
                $place = explode('.', $key)[0]; // Récupère le nom de la place
                $results[$place] = $value;
            }
        }
        if (!empty($results)) {
            return [
                'time' => $defaultCronTime,
                'execute' => $results
            ];
        } else {
            return $results;
        }
    }
}
