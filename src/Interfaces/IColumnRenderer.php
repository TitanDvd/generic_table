<?php

namespace Mmt\GenericTable\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface IColumnRenderer
{
    /**
     * 
     * Applys custom format to the HTML output
     * 
     */
    public function renderCell(Model $rowModel) : string;
}