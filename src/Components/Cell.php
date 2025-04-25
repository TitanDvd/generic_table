<?php

namespace Mmt\GenericTable\Components;

use Illuminate\Database\Eloquent\Model;
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

        if( Column::columnIsRelationship($column->databaseColumnName) && $model instanceof Model) {
            $cellValue = self::getNestedRelationValue($model, $column->databaseColumnName);
        }
        else {
            $cellValue = $model->{$column->databaseColumnName};
        }

        $cell->value = $cellValue;
        return $cell;
    }

    public static function getNestedRelationValue(Model $model, string $relationPath)
    {
        $relations = explode('.', $relationPath);
        foreach ($relations as $relation) {
            $model = $model->{$relation};
        }
        return $model;
    }
}