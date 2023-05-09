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
    public $places;
    public $trans;
    public $config;

    public function __construct($name)
    {
        $this->name = $name;
    }
    public function sheets(): array
    {
        $places = $this->name . '_places';
        $trans = $this->name . '_trans';
        $config = $this->name . '_work';

        return [
            $places => $this->places = new PlacesImport(),
            $trans => $this->trans = new TransImport(),
            $config => $this->config = new configImport(),
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
                'label' => $row['label'] ?? null,
            ];
        }
        return $this->data;
    }
}
class PlacesImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas
{

    public $data;

    public function collection(Collection $rows)
    {
        $this->data = [];
        foreach ($rows as $row) {
            $obj = [
                'name' => $row['name'] ?? null,
                'lang' => $row['lang'] ?? null,
                'com' => $row['com'] ?? null,
                'alerte' => $row['alerte'] ?? null,
                'icon' => $row['icon'] ?? null,
                'color' => $row['color'] ?? null,
                'permissions' => $row['permissions'] ?? null,
                'cron_auto' => $row['cron_auto'] ?? null,
                'form_auto' => $row['form_auto'] ?? null,
                'must_trans' => $row['must_trans'] ?? null,
                'hidden_fields' => $row['hidden_fields'] ?? null,
                'ro_fields' => $row['ro_fields'] ?? null,
                'new_workflow' =>  $row['new_workflow'] ?? null,
            ];
            array_push($this->data, $obj);
        }
        return $this->data;
    }
}

class TransImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas
{

    public $data;

    public function collection(Collection $rows)
    {
        $this->data = [];
        foreach ($rows as $row) {
            $obj = [
                'froms' => $row['froms'] ?? null,
                'from' => $row['from'] ?? null,
                'to' => $row['to'] ?? null,
                'name' => $row['final_name'] ?? null,
                'lang' => $row['lang'] ?? null,
                'button' => $row['button'] ?? null,
                'com' => $row['com'] ?? null,
                'redirect' => $row['redirect'] ?? null,
                'rules' => $row['rules'] ?? null,
                'color' => $row['color'] ?? null,
                'goto'  => $row['goto'] ?? null,
                'permissions' => $row['permissions'] ?? null,
                'hidden' => $row['hidden'] ?? null,
                'fnc_gard' => $row['fnc_gard'] ?? null,
                'fnc_enter' => $row['fnc_enter'] ?? null,
                'fnc_entered' => $row['fnc_entered'] ?? null,
                'fnc_prod' => $row['fnc_prod'] ?? null,
                'fnc_gard' => $row['fnc_gard'] ?? null,
                'fnc_gard_val' => $row['fnc_gard_val'] ?? null,
            ];
            array_push($this->data, $obj);
        }
        return $this->data;
    }
}
