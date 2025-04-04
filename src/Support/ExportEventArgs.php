<?php

namespace Mmt\GenericTable\Support;

use Arr;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Mmt\GenericTable\Components\ColumnCollection;
use Mmt\GenericTable\Enums\ColumnSettingFlags;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportEventArgs
{
    public array $headers;
    public array $rows;

    public function __construct(
        private ColumnCollection $columns, 
        public ExportSettings $settings, 
        public Builder $query, 
        private Closure $bindersCallback,
        private array $formatters
    ) { }

    /**
     * 
     * Populates `$headers` and `$rows` properties with all of its respective values.
     * This method can cause deadlocks with huge amount of data. Formatters are applied
     * if they exists and useFormatters option is `true`
     * 
     */
    public function populate(?ColumnCollection $newColumns = null)
    {
        if(isset($newColumns)) {
            $this->columns = $newColumns;
        }

        /** @var Builder */
        $queryResult = $this->query->get();

        foreach ($this->columns as $column) {
            if(ColumnSettingFlags::hasFlag($column->settings, ColumnSettingFlags::EXPORTABLE)) {
                $this->headers[] = $column->columnTitle;
                $rowIndex = 0;
                foreach ($queryResult as $item) {
                    if(isset($this->formatters[$column->databaseColumnName]) && $this->settings->useFormatters) {
                        $this->rows[$rowIndex][] = $this->bindersCallback->__invoke($this->formatters[$column->databaseColumnName], $item);
                    }
                    else {
                        $this->rows[$rowIndex][] = $item->{$column->databaseColumnName};
                    }
                    $rowIndex++;
                }
            }
        }
    }


    /**
     * 
     * Runs internally the populate method and then the export method
     * from the settings class
     * 
     * @see \Mmt\GenericTable\Support\ExportSettings::export
     * 
     */
    public function export() :  BinaryFileResponse|Response
    {
        $this->populate();
        return $this->settings->export($this->headers, $this->rows);
    }
}