<?php

namespace Mmt\GenericTable\Support;

class SelectionFilterSettings
{
    public private(set) array $possibleValues = [];


    public function __construct(
        public string $databaseColumnName    
    ) { }

    public function add(string $filterLabel, string $filterValue) : self
    {
        $this->possibleValues[$filterLabel] = $filterValue;
        return $this;
    }
}