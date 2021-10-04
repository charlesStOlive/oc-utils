<?php namespace Waka\Utils\Facades;

use October\Rain\Support\Facade;

class DataSources extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor() { return 'datasources'; }
}
