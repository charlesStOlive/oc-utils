<?php namespace Waka\Utils\Classes;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*
use Config;
use October\Rain\Support\Collection;
use Yaml;

class DataSourceList
{

    public static function lists($valueToShow = 'label', $valueToSave = 'code')
    {
        $result = new Collection(self::getSrConfig());
        return $result->pluck($valueToShow, $valueToSave);
    }
    public static function getValue($data_source)
    {
        $result = new Collection(self::getSrConfig());
        return $result->where('code', $data_source)->first()['label'] ?? 'inc';
    }
    /**
     * GLOBAL
     */

    public static function getSrConfig()
    {
        $dataSource = Config::get('wcli.wconfig::data_source.src');
        if ($dataSource) {
            return Yaml::parseFile(plugins_path() . $dataSource);
        } else {
            return Yaml::parseFile(plugins_path() . '/wcli/wconfig/config/datasources.yaml');
        }
    }
}
