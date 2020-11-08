<?php namespace Waka\Utils\Classes;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*
use Config;
use October\Rain\Support\Collection;
use Yaml;

class DataSourceList
{

    public static function lists($valueToShow = 'name', $valueToSave = 'id')
    {
        $result = new Collection(self::getSrConfig());
        return $result->pluck($valueToShow, $valueToSave);
    }
    public static function getValue($data_source_id)
    {
        $result = new Collection(self::getSrConfig());
        return $result->where('id', $data_source_id)->first()['name'] ?? 'inc';
    }
    /**
     * GLOBAL
     */

    public static function getSrConfig()
    {
        $dataSource = Config::get('waka.wconfig::data_source.src');
        if ($dataSource) {
            return Yaml::parseFile(plugins_path() . $dataSource);
        } else {
            return Yaml::parseFile(plugins_path() . '/waka/wconfig/config/datasources.yaml');
        }

    }

}
