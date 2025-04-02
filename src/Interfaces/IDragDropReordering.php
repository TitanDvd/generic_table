<?php

namespace Mmt\GenericTable\Interfaces;

interface IDragDropReordering
{
    /**
     * 
     * Overwrites the behavior of the attribute DEFAULT_SORT_XXX.
     * Enforces system to use this column as a default sorting column.
     * If you dont assign a value to this property system will use "order"
     * as a string to locate the ordering column. Make sure ordering column exists in your database
     * and is ordered starting from 1
     * 
     */
    public string $orderingColumn { get; set; }
}