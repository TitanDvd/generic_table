<?php

namespace Mmt\GenericTable\Interfaces;


interface IColumnRenderer
{
    /**
     * 
     * Applys custom format to the HTML output
     * 
     */
    public function renderCell(ICellData $cell, IRowData $row) : string|null;
}