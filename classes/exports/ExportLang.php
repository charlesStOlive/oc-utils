<?php

namespace Waka\Utils\Classes\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExportLang implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $allData;
    protected $fileName;
    protected $fileContent;
    
    public function __construct($fileName, $fileContent, $langs)
    {
        $this->fileName = $fileName;
        $this->fileContent = $fileContent;
        $this->langs = $langs;
    }

    public function headings(): array
    {
        $startHeader = [
            'key',
            'fr',
        ];
        $startHeader = array_merge($startHeader, $this->langs);
        return $startHeader;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            'A'=> [
                'font' => ['bold' => true],
            ],
            1=> [
                'font' => ['bold' => true],
            ],
            'A1:A50' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFF0000'],
                ],
            ],
        ];
    }

    public function collection()
    {
        //trace_log($this->fileName);
        //trace_log($this->fileContent);
        $baseLangContent = new Collection($this->fileContent['fr']);
        
        $baseLangContent = $baseLangContent->map(function ($item, $key) {
            $langData = [];
            $langData['key'] = $key;
            $langData['fr'] = $item;
            foreach($this->langs as $lang) {
                $langData[$lang] = $this->fileContent[$lang][$key];
            }
            return $langData;
        });
        return $baseLangContent;
    }

    

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->fileName;
    }
}
