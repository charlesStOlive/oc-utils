<?php

namespace Waka\Utils\Classes\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportModelController implements WithMultipleSheets
{
    public $model;
    public $data;
    public $config;
    public $relations;

    public function __construct($model)
    {
        $this->model = $model;
    }
    public function sheets(): array
    {
        $config = $this->model . '_config';
        $data = $this->model . '_data';
        $relations = $this->model . '_relations';

        return [
            $config => $this->config = new configImport(),
            $data => $this->data = new dataImport(),
            $relations => $this->relations = new RelationImport(),
        ];
    }
}

class ConfigImport implements ToCollection, WithHeadingRow
{

    public $data;

    public function collection(Collection $rows)
    {
        $this->data = [];
        foreach ($rows as $row) {
            $this->data[$row['key']] = $row['value'];
        }
        return $this->data;
    }
}
class DataImport implements ToCollection, WithHeadingRow
{

    public $data;

    public function collection(Collection $rows)
    {
        $this->data = [];
        foreach ($rows as $row) {
            $obj = [
                'var' => $row['var'] ?? null,
                'name' => $row['nom'] ?? null,
                'comment' => $row['comment'] ?? null,
                'attribute' => $row['attribute'] ?? null,
                'type' => $row['type'] ?? null,
                'column' => $row['colonne'] ?? null,
                'col_opt' => $row['col_opt'] ?? null,
                'not_null' => $row['not_null'] ?? null,
                'permissions' => $row['permissions'] ?? null,
                'field' => $row['field'] ?? null,
                'c_field' => $row['c_field'] ?? null,
                //'context' => $row['context'] ?? null,
                'required' => $row['requis'] ?? null,
                'model_opt' => $row['model_opt'] ?? null,
                // 'title' => $row['titre'] ?? null,
                // 'append' => $row['append'] ?? null,
                // 'json' => $row['json'] ?? null,
                // 'getter' => $row['getter'] ?? null,
                // 'purgeable' => $row['purgeable'] ?? null,
                'relation' => $row['relation'] ?? null,
                'default' => $row['default'] ?? null,
                'span' => $row['span'] ?? null,
                'field_type' => $row['field_type'] ?? null,
                'field_options' => $row['field_options'] ?? null,
                'c_field_opt' => $row['c_field_opt'] ?? null,
                'lists' => $row['lists'] ?? null,
                'trigger' => $row['trigger'] ?? null,
                'tab' => $row['tab'] ?? null,
                'excel' => $row['excel'] ?? null,
                'version' => $row['version'] ?? null,
            ];
            array_push($this->data, $obj);
        }
        return $this->data;
    }
}

class RelationImport implements ToCollection, WithHeadingRow
{

    public $data;

    public function collection(Collection $rows)
    {
        $this->data = [];
        foreach ($rows as $row) {
            $obj = [
                'var' => $row['var'] ?? null,
                'type' => $row['type'] ?? null,
                'class' => $row['class'] ?? null,
                'options' => $row['options'] ?? null,
                'columns' => $row['columns'] ?? null,
                'fields' => $row['fields'] ?? null,
                'yamls' => $row['yamls'] ?? null,
                'yamls_read' => $row['yamls_read'] ?? null,
                'toolbar' => $row['toolbar'] ?? null,
                'search' => $row['search'] ?? null,
                'show_search' => $row['show_search'] ?? null,
                'sort_column' => $row['sort_column'] ?? null,
                'sort_mode' => $row['sort_mode'] ?? null,
                'filters' => $row['filters'] ?? null,
                
            ];
            array_push($this->data, $obj);
        }
        return $this->data;
    }
}
