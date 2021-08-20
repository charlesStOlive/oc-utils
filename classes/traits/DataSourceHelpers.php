<?php namespace Waka\Utils\Classes\Traits;

trait DataSourceHelpers
{
    public function listDataSource()
    {
        return \Waka\Utils\Classes\DataSourceList::lists();
    }

    public function listAllDataSourceToArray()
    {
        return $this->listDataSource()->toArray();
    }
    public function listDataSourceTarget()
    {
        if(!$this->data_source) {
            return [];
        }
        //trace_log($this->data_source);
        $ds = new \Waka\Utils\Classes\DataSource($this->data_source);
        $class = new $ds->class;
        return $class::orderBy('updated_at', 'desc')->limit(200)->get()->lists($ds->outputName, 'id');
    }
}
