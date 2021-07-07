<?php

namespace Waka\Utils\Classes\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportLang implements WithMultipleSheets
{
    public $file;
    public $langs;
    public $sheetArray;

    public function __construct($files, $langs)
    {
        $this->files = $files;
        $this->langs = $langs;
        $this->sheetArray = [];
    }
    public function sheets(): array
    {
        foreach($this->files as $file) {
            $this->sheetArray[$file] = new LangImport($this->langs);
        }
        return $this->sheetArray;
    }
}

class LangImport implements ToCollection, WithHeadingRow
{
    public $langs;
    public $data;

    public function __construct($langs)
    {
        $this->langs = $langs;
        $this->data = [];
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $key = $row['key'];
            $this->data['fr'][$key] = $row['fr'];
            foreach($this->langs as $lang) {
                $this->data[$lang][$key] = $row[$lang] ?? null;
            }
        }
        return $this->data;
    }
}
