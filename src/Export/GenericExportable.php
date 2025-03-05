<?php

namespace Mmt\GenericTable\Export;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use ReflectionClass;

class GenericExportable implements FromArray, WithHeadings, WithColumnWidths, WithEvents, WithTitle
{
    use Exportable;

    private string $SheetTitle;
    private array $Headers;
    private array $Rows;
    private array $Letters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];


    public function __construct(array|string $headers, array $rows, string $sheetTitle = null)
    {
        if(is_string($headers))
        {
            $this->Headers = $this->GetHeadersFromFQCN($headers);
        }
        else
            $this->Headers = $headers;
        $this->Rows    = $rows;
        $this->SheetTitle = $sheetTitle == null ? 'Worksheet-1' : $sheetTitle;
    }


    private function GetHeadersFromFQCN(string $fqcn)
    {
        $h = [];
        $reflectionClass = new ReflectionClass($fqcn);
        foreach ($reflectionClass->getProperties() as $props) {
            $h[] = $props->name;
        }
        return $h;
    }


    public function headings() : array
    {
        return $this->Headers;
    }

    
    public function array() : array
    {
        return $this->Rows;
    }

    public function columnWidths(): array
    {
        $c = [];
        $i = 0;
        //
        // $this->Rows[0] es la primera muestra para establecer el width maximo de la columna
        //
        foreach ($this->Rows[0] ?? [] as $propertyName => $propertyValue) {
            $len = strlen($propertyValue);
            $c[$this->Letters[$i++]] = $len < 16 ? 16 : $len;
        }
        return count($c) ? $c : [];
    }


    public function registerEvents(): array
    {
        $s = 1;
        $e = count($this->Headers);
        $sl = $this->Letters[0];
        $el = $this->Letters[$e-1];
        $st = "{$sl}{$s}:{$el}{$s}";
        
        return [

            AfterSheet::class => function(AfterSheet $event)use($st){
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(45);
                $event->sheet->getDelegate()->getStyle($st)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle($st)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            },
        ];
    }


    public function title() : string
    {
        return $this->SheetTitle;
    }
}
