<?php

namespace Mmt\GenericTable\Support;

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
        public Builder|\Illuminate\Database\Query\Builder $query, 
        private Closure $bindersCallback,
        private array $formatters,
        public bool $useStripTags,
        public string $excludeTags 
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
                        $cellValue = $this->bindersCallback->__invoke($this->formatters[$column->databaseColumnName], $item);
                    }
                    else {
                        $cellValue = $item->{$column->databaseColumnName};
                    }
                    $this->rows[$rowIndex][] = $this->useStripTags == true ? strip_tags($cellValue, $this->excludeTags) : $cellValue;
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