<?php

namespace Mmt\GenericTable\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Mmt\GenericTable\Components\ColumnCollection;

interface IGenericTable
{
    public Model|string $model {get; set;}

    public ColumnCollection $columns {get; set;}
}