<?php

namespace Mmt\GenericTable\Traits;

use Mmt\GenericTable\Enums\FilterType;
use Mmt\GenericTable\Interfaces\ISingleSelectionFilter;
use Str;

trait WithSingleSelectionFilter
{
    public string $appliedSingleSelectionFilters = '';

    private function singleSelectionFiltersInUse(&$filtersInUse)
    {
        if(empty($this->appliedSingleSelectionFilters)) {
            return;
        }

        $jsonObj = json_decode($this->appliedSingleSelectionFilters);
    
        $filtersInUse[] = [
            'type'   => 'single',
            'column' => $jsonObj->column,
            'label'  => $jsonObj->label,
            'value'  => $jsonObj->value
        ];
        
    }

    
    public function removeSingleSelectionFilter() : void
    {
        $this->appliedSingleSelectionFilters = '';
    }


    public function applySingleSelectionFilterToQuery(&$query)
    {
        $jsonObj = json_decode($this->appliedSingleSelectionFilters);

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
        if($this->tableObject instanceof ISingleSelectionFilter) {
            $this->filters->add($this->tableObject->singleSelectionFilterSettings->databaseColumnName)
            ->with($this->tableObject->singleSelectionFilterSettings->possibleValues)
            ->as(FilterType::SINGLE_SELECTION);
        }
    }
}