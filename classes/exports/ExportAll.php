<?php

namespace Waka\Utils\Classes\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ExportAll implements WithMultipleSheets
{
    use Exportable;

    protected $data;
    protected $langs;
    
    public function __construct(array $data, array $langs)
    {
        $this->data = $data;
        $this->langs = $langs;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        trace_log('sheets');
        $sheets = [];

        $srcLang = $this->data;
        trace_log($srcLang);
        foreach ($srcLang as $fileName=>$fileContent) {
            $sheets[] = new ExportLang($fileName, $fileContent, $this->langs);
        }

        return $sheets;
    }
}
