<?php

namespace Mmt\GenericTable\Traits;

use Mmt\GenericTable\Enums\FilterType;
use Mmt\GenericTable\Interfaces\IMultiSelectionFilter;
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
                    
                    $query->orWhereHas($relationShip, function($q) use($column, $value) {
                        $q->where($column, $value);
                    });
                }
                else {
                    $query->orWhere($jsonObj->column, $jsonObj->value);
                }
            }
        }
    }

    private function multiSelectionFiltersInUse(&$filtersInUse)
    {
        foreach($this->appliedMultiSelectionFilters as $selectionJsonValue) {
            $jsonObj = json_decode($selectionJsonValue);
            $filtersInUse[] = [
                'type'   => 'multi',
                'column' => $jsonObj->column,
                'label'  => $jsonObj->label,
                'value'  => $jsonObj->value
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
        if($this->tableObject instanceof IMultiSelectionFilter) {
            $this->filters->add($this->tableObject->multiSelectionFilterSettings->databaseColumnName)
            ->with($this->tableObject->multiSelectionFilterSettings->possibleValues)
            ->as(FilterType::MULTI_SELECTION);
        }
    }
}