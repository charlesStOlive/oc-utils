<?php namespace Waka\Utils\Classes\Ds;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*
use ApplicationException;
use Config;
use Winter\Storm\Support\Collection;
use Winter\Storm\Extension\ExtensionBase;
use Ds;
use Yaml;
use Waka\Utils\Classes\Ds\DataSource;

class DataSources extends ExtensionBase
{

    private static $datasources = [];

    public static function registerDataSources($data) {
        // trace_log('registerDataSources');
        // trace_log($data);
        $array = [];
        if(is_string($data)) {
            $data = \Yaml::parseFile($data);
            $ds = array_get($data, 'datasource');
            self::$datasources = array_merge(self::$datasources, $ds);
        }
        elseif(is_array($data)) {
            array_push(self::$datasources, $data);
        }
        
    }

    public static function list() {
        $all = self::$datasources;
        $list = [];
        foreach($all as $key=>$value) {
            $list[$key] = $value['label'];
        }
        return $list;

    }
    public static function getLabel($code) {
        $allDatasources = self::$datasources;
        return $allDatasources['code']['label'] ?? 'error Ds getValue';
    }
    /**
     * RETURN DataSource
     */

    public static function find($finderKey, $finderType = 'code') {
        $dsKey = null;
        $allDatasources = self::$datasources;
        $dataSourceConfig = null;
        if($finderType == 'code') {
            if($allDatasources[$finderKey] ?? false) {
                $dataSourceConfig = $allDatasources[$finderKey];
                $dsKey = $finderKey;
            }
        } else if($finderType == 'class') {
            foreach($allDatasources as $rowKey=>$value) {
                if($value['class'] == $finderKey) {
                    $dataSourceConfig = $allDatasources[$rowKey];
                    $dsKey = $rowKey;
                    break;
                }
            }

        }
        // trace_log("datasource config : ");
        // trace_log($dataSourceConfig);
        if(!$dataSourceConfig) {
            throw new ApplicationException("La configuration datasource n' a pas été trouvé pour la valeur :  ".$finderKey." recherché en mode ".$finderType);
        } else {
            return new DataSource($dataSourceConfig, $dsKey);
        }
        
    }

    



}
