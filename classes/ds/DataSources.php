<?php namespace Waka\Utils\Classes\Ds;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*
use ApplicationException;
use Config;
use Winter\Storm\Support\Collection;
use Winter\Storm\Extension\Extendable;
use Ds;
use Yaml;
use Waka\Utils\Classes\Ds\DataSource;

class DataSources extends Extendable
{

    private static $datasources = [];

    public static function registerDataSources($data) {
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

    public static function list($filter = null) {
        $all = self::$datasources;
        $list = [];
        foreach($all as $key=>$value) {
            if($filter) {
                $filterOk = $value[$filter] ?? false;
                if(!$filterOk) {
                    continue;
                }
            }
            $hidden = $value['hidden'] ?? false;
            if(!$hidden) {
                $list[$key] = $value['label'];
            }
        }
        return $list;

    }
    public static function getLabel($code) {
        $allDatasources = self::$datasources;
        return $allDatasources[$code]['label'] ?? 'error Ds getValue';
    }
    /**
     * RETURN DataSource
     */
    public static function find($finderKey) {
        $dsKey = null;
        $allDatasources = self::$datasources;
        $dataSourceConfig = null;
        if($allDatasources[$finderKey] ?? false) {
            $dataSourceConfig = $allDatasources[$finderKey];
            $dsKey = $finderKey;
        }
        if(!$dataSourceConfig) {
            throw new ApplicationException("La configuration datasource n' a pas été trouvé pour la valeur :  ".$finderKey." recherché sur le code");
        } else {
            return new DataSource($dataSourceConfig, $dsKey);
        }
    }

    public static function findByClass($finderKey) {
        $dsKey = null;
        $allDatasources = self::$datasources;
        $dataSourceConfig = null;
        foreach($allDatasources as $rowKey=>$value) {
            if($value['class'] == $finderKey) {
                $dataSourceConfig = $allDatasources[$rowKey];
                $dsKey = $rowKey;
                break;
            }
        }
        if(!$dataSourceConfig) {
            throw new ApplicationException("La configuration datasource n' a pas été trouvé pour la valeur :  ".$finderKey." recherché sur le class");
        } else {
            return new DataSource($dataSourceConfig, $dsKey);
        }
        
    }

    



}
