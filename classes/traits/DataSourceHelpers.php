<?php namespace Waka\Utils\Classes\Traits;

trait DataSourceHelpers
{
    public function listDataSource()
    {
        return \Waka\Utils\Classes\DataSourceList::lists();
    }
    public function listDataSourceTarget()
    {
        //trace_log($this->data_source_id);
        $ds = new \Waka\Utils\Classes\DataSource($this->data_source_id, 'id');
        $class = new $ds->class;
        return $class::orderBy('updated_at', 'desc')->limit(200)->get()->lists($ds->outputName, 'id');

    }
}
