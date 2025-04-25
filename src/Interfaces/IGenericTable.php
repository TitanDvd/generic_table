<?php

namespace Mmt\GenericTable\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Mmt\GenericTable\Components\ColumnCollection;
use Mmt\GenericTable\Support\GenericTableSettings;

interface IGenericTable
{
    public GenericTableSettings $tableSettings {get; set;}
}