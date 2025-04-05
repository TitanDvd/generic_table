<?php

namespace Mmt\GenericTable\Traits;

use Mmt\GenericTable\Enums\FilterType;
use Mmt\GenericTable\Interfaces\IColumnFilter;
use Mmt\GenericTable\Support\SingleFilter;
use Str;

trait WithSingleSelectionFilter
{
    public array $appliedSingleSelectionFilters = [];

    private function singleSelectionFiltersInUse(&$filtersInUse)
    {
        if(empty($this->appliedSingleSelectionFilters)) {
            return;
        }

        foreach($this->appliedSingleSelectionFilters as $filterInJson) {
            $this->setFiltersInUse($filterInJson, $filtersInUse);
        }
    }

    private function setFiltersInUse(string $json, array &$filtersInUse)
    {
        $jsonObj = json_decode($json);
    
        $filtersInUse[] = [
            'type'   => 'single',
            'column' => $jsonObj->column,
            'label'  => $jsonObj->label,
            'value'  => $jsonObj->value
        ];
    }

    
    public function removeSingleSelectionFilter(string $column, string $value) : void
    {
        $newArr = [];
        foreach ($this->appliedSingleSelectionFilters as $filterJson) {
            $jsonObj = json_decode($filterJson);
            if($column != $jsonObj->column || $value != $jsonObj->value){
                $newArr[] = $filterJson;
            }
        }
        $this->appliedSingleSelectionFilters = $newArr;
    }


    public function applySingleSelectionFilterToQuery(&$query)
    {
        foreach ($this->appliedSingleSelectionFilters as $filterJsonInfo) {
            $this->applyFilter($filterJsonInfo, $query);
        }
    }

    private function applyFilter($jsonFilter, &$query)
    {
        $jsonObj = json_decode($jsonFilter);

        if($jsonObj == null || $jsonObj->value == -1)
            return;
        
        if(Str::contains($jsonObj->column, '.')) {

            $lastDotPosition = strrpos($jsonObj->column, '.');
            $relationship = substr($jsonObj->column, 0, $lastDotPosition);
            $column = substr($jsonObj->column, $lastDotPosition + 1);
            $value = $jsonObj->value;

            $query->whereHas($relationship, function($q) use($column, $value) {
                $q->where($column, $value);
            });
        }
        else {
            $query->where($jsonObj->column, $jsonObj->value);
        }
    }

    /**
     * 
     * @internal
     * 
     */
    public function updatedAppliedSingleSelectionFilters()
    {
        $this->resetPagination();
    }

    public function registerSingleSelectionFilter() : void
    {
        if($this->tableObject instanceof IColumnFilter) {
            foreach ($this->tableObject->filters as $filter) {
                if($filter instanceof SingleFilter) {
                    $this->filters->add($filter->databaseColumnName)
                    ->with($filter->possibleValues)
                    ->as(FilterType::SINGLE_SELECTION);
                }
            }
        }
    }
}