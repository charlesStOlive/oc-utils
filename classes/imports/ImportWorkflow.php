<?php

namespace Waka\Utils\Classes\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportWorkflow implements WithMultipleSheets
{
    public $name;
    public $data;
    public $config;

    public function __construct($name)
    {
        $this->name = $name;

    }
    public function sheets(): array
    {
        $config = $this->name . '_wonfig';
        $data = $this->name . '_wata';

        return [
            $config => $this->config = new configImport(),
            $data => $this->data = new dataImport(),
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
            $this->data[$row['key']] = [
                'key' => $row['key'] ?? null,
                'type' => $row['type'] ?? null,
                'value' => $row['value'] ?? null,
                'data' => $row['data'] ?? null,
            ];
        }
        return $this->data;
    }

}
class DataImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas
{

    public $data;

    public function collection(Collection $rows)
    {
        $this->data = [];
        foreach ($rows as $row) {
            $obj = [
                'var' => $row['var'] ?? null,
                'key' => $row['key'] ?? null,
                'type' => $row['type'] ?? null,
                'lang' => $row['lang'] ?? null,
                'com' => $row['com'] ?? null,
                'from' => $row['from'] ?? null,
                'to' => $row['to'] ?? null,
                'rules' => $row['rules'] ?? null,
                'color' => $row['color'] ?? null,
                'icon' => $row['icon'] ?? null,
                'fnc_prod' => $row['fnc_prod'] ?? null,
                'fnc_prod_arg' => $row['fnc_prod_arg'] ?? null,
                'fnc_prod_val' => $row['fnc_prod_val'] ?? null,
                'fnc_trait' => $row['fnc_trait'] ?? null,
                'fnc_trait_arg' => $row['fnc_trait_arg'] ?? null,
                'fnc_trait_att' => $row['fnc_trait_att'] ?? null,
            ];
            array_push($this->data, $obj);
        }
        return $this->data;
    }

}
