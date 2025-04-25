<?php

namespace Mmt\GenericTable\Support;

class FilterSettings
{
    public private(set) array $possibleValues = [];

    public string $customLabel;

    public function __construct(public string $databaseColumnName, array $possibleValues = [], string $label = '', public private(set) bool $showLabel = true)
    {
        if($label == '')
            $this->customLabel = $this->databaseColumnName . ':';
        else
            $this->customLabel = $label;

        foreach ($possibleValues as $key => $value) {
            $this->add($key, $value);
        }
    }

    /**
     * 
     * Add a possible filter value to the filter collection
     * 
     */
    public function add(string $filterLabel, string $filterValue) : self
    {
        $this->possibleValues[$filterLabel] = $filterValue;
        return $this;
    }

    public static function make(string $databaseColumnName, array $possibleValues = [], string $label = '',  bool $showLabel = true) : static
    {
        return new static($databaseColumnName, $possibleValues, $label, $showLabel);
    }
}