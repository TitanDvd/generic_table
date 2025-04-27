<?php

namespace Mmt\GenericTable\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Mmt\GenericTable\Interfaces\ICellData;
use Mmt\GenericTable\Interfaces\IColumn;
use Mmt\GenericTable\Interfaces\IRowData;
use stdClass;
use function Pest\Laravel\instance;

class Cell implements ICellData
{
    public int $index;

    public $value;

    public static function make(IColumn $column, Model|stdClass $model) : static
    {
        $cell = new static;
        $cell->index = $column->columnIndex;
        
        if($column->isRelationship()) {
            $cell->value = $model->{Str::snake($column->columnTitle)};
        }
        else {
            $cell->value = $model->{$column->databaseColumnName};
        }

        return $cell;
    }
}