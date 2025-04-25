<?php

namespace Mmt\GenericTable\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use stdClass;

interface IActionColumn
{
    /**
     * 
     * Indicates where to render the column.
     * If values is -1, the column will always be rendered
     * as the last column
     * 
     */
    public int $actionColumnIndex {get; set;}

    /**
     * 
     * The view to be used to render HTML controls
     * 
     */
    public function actionView(IRowData $row) : View;
}