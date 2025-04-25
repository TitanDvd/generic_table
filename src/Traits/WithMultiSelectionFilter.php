<?php

namespace Mmt\GenericTable\Traits;

use Mmt\GenericTable\Enums\FilterType;
use Mmt\GenericTable\Interfaces\IColumnFilter;
use Mmt\GenericTable\Support\MultiFilter;
use Str;

trait WithMultiSelectionFilter
{
    public array $appliedMultiSelectionFilters = [];


    public function applyMultiSelectionFilterToQuery(&$query)
    {
        if(count($this->appliedMultiSelectionFilters) > 0) {
            foreach($this->appliedMultiSelectionFilters as $selectionJsonValue) {
                
                $jsonObj = json_decode($selectionJsonValue);

                if(Str::contains($jsonObj->column, ".")) {
                    $lastDotPosition = strrpos($jsonObj->column, '.');
                    $relationShip = substr($jsonObj->column, 0, $lastDotPosition);
                    $column = substr($jsonObj->column, $lastDotPosition + 1);
                    $value = $jsonObj->value;
                    
                    $query->whereHas($relationShip, function($q) use($column, $value) {
                        $q->where($column, $value);
                    });
                }
                else {
                    $query->where($jsonObj->column, $jsonObj->value);
                }
            }
        }
    }

    private function multiSelectionFiltersInUse(&$filtersInUse)
    {
        foreach($this->appliedMultiSelectionFilters as $selectionJsonValue) {
            $jsonObj = json_decode($selectionJsonValue, true);
            $filtersInUse[] = [
                'type'   => 'multi',
                ...$jsonObj
            ];
        }
    }

    public function removeFromMultiSelectionFilter($column, $value)
    {
        $newArr = [];
        foreach($this->appliedMultiSelectionFilters as $selectionJsonValue) {
            $jsonObj = json_decode($selectionJsonValue);
            if($column != $jsonObj->column || $value != $jsonObj->value){
                $newArr[] = $selectionJsonValue;
            }
        }
        $this->appliedMultiSelectionFilters = $newArr;
    }

    /**
     * 
     * @internal
     * 
     */
    public function updatedAppliedMultiSelectionFilters()
    {
        $this->resetPagination();
    }

    private function registerMultiSelectionFilters()
    {
        if($this->tableObject instanceof IColumnFilter) {
            foreach ($this->tableObject->filters as $filter) {
                if($filter instanceof MultiFilter) {
                    $this->filters->add($filter->databaseColumnName)
                    ->with($filter->possibleValues)
                    ->useLabel($filter->showLabel)
                    ->label($filter->customLabel)
                    ->as(FilterType::MULTI_SELECTION);
                }
            }
        }
    }
}