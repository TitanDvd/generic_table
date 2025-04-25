<?php

namespace Mmt\GenericTable\Components;

use Illuminate\Database\Eloquent\Model;
use Mmt\GenericTable\Interfaces\IRow;
use Mmt\GenericTable\Interfaces\IRowData;
use stdClass;

class Row implements IRowData
{
    public Model|stdClass $origin;

    public function get(string $column) : mixed
    {
        if(isset($this->origin)) {
            return $this->origin->{$column};
        }
        return null;
    }

    public static function make(Model|stdClass $model) : static
    {
        $row = new Row();
        $row->origin = $model;
        return $row;
    }
}