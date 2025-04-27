<?php

namespace Mmt\GenericTable\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Mmt\GenericTable\Attributes\MappedRoute;


/**
 * Use it to build custom columns as well.
 */
interface IColumn
{
    /**
     * 
     * The label used for the column header in the html output
     * 
     */
    public string $columnTitle {get; set;}

    /**
     * 
     * Sets the column to be used in the database
     * 
     */
    public ?string $databaseColumnName {get; set;}


    /**
     * 
     * Defines the settings for the column
     * @see \Mmt\GenericTable\Enums\ColumnSettingFlags
     * 
     */
    public int $settings {get; set;}


    /**
     * 
     * Defines a route for the column
     * 
     */
    public ?MappedRoute $mappedRoute {get; set;}

    
    public int $columnIndex {get; set;}

    public function isRelationship() : bool;
}