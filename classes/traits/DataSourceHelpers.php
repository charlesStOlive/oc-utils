<?php namespace Waka\Utils\Classes\Traits;

trait DataSourceHelpers
{
    public function listDataSource()
    {
        return \DataSources::list();
    }

    public function listDataSourceTarget($limit = 500)
    {
        if(!$this->data_source) {
            return [];
        }
        $ds = \DataSources::find($this->data_source);
        if(!$limit) {
            $limit = 100;
        }
        $class = new $ds->class;
        $options = $class::orderBy('updated_at', 'desc')->limit($limit)->get()->lists($ds->outputName, 'id');
        return $options;
    }
}
