<?php

namespace Mmt\GenericTable\Traits;

use Mmt\GenericTable\Components\TableFilterItem;
use Mmt\GenericTable\Enums\CommonDateFilter;
use Mmt\GenericTable\Enums\FilterType;
use Mmt\GenericTable\Interfaces\IDateRangeFilter;
use Str;


trait WithDateFilters
{
    public string $customStartDateFilter = '';
    public string $customEndDateFilter = '';
    public string $dateRangeAppliedFilters = '';

    public array $commonDateFilters
    {
        get => CommonDateFilter::cases();
        set => $value;
    }

    public function isDateFilter(TableFilterItem $filter): bool
    {
        return $filter->type == FilterType::DATE_RANGE;
    }

    public function isCustomFilterApplied() : bool
    {
        if(empty($this->dateRangeAppliedFilters))
            return false;

        $jsonObj = json_decode($this->dateRangeAppliedFilters);
        
        return $jsonObj->value == CommonDateFilter::CUSTOM_RANGE->value;
    }

    public function removeCustomDateRangeFilter()
    {
        $this->customStartDateFilter = '';
        $this->customEndDateFilter = '';
        $this->dateRangeAppliedFilters = '';
    }

    private function dateFiltersInUse(&$filtersInUse)
    {
        if(empty($this->dateRangeAppliedFilters))
            return;
            
        $jsonObj = json_decode($this->dateRangeAppliedFilters);

        $dateRangeFilter = CommonDateFilter::from($jsonObj->value);

        if($dateRangeFilter == CommonDateFilter::CUSTOM_RANGE) {
            if(!empty($this->customStartDateFilter) && !empty($this->customEndDateFilter))
                $dateRange = [$this->customStartDateFilter, $this->customEndDateFilter];
        }
        else {
            $dateSlots = CommonDateFilter::getDateRange($dateRangeFilter);
            $dateRange = [$dateSlots[0]->format('Y-m-d H:i:s'), $dateSlots[1]->format('Y-m-d H:i:s')];
        }
        
        if(isset($dateRange)) {
            $filtersInUse[] = [
                'type' => 'date',
                'column' => $jsonObj->column,
                'label' => "{$dateRange[0]} - {$dateRange[1]}",
                'value' => 'Date Range',
                'showLabel' => $jsonObj->showLabel,
                'customLabel' => $jsonObj->customLabel
            ];
        }
    }

    private function applyDateFilterToQuery(&$query)
    {
        if(empty($this->dateRangeAppliedFilters))
            return;

        $jsonObj = json_decode($this->dateRangeAppliedFilters);

        if(CommonDateFilter::hasFlag($jsonObj->value, CommonDateFilter::CUSTOM_RANGE)) {
            if($this->customStartDateFilter != '' && $this->customEndDateFilter != '') {
                $dateRange = [$this->customStartDateFilter, $this->customEndDateFilter];
            }
        }
        else {
            $filterFlag = CommonDateFilter::from($jsonObj->value);
            $dateRange = CommonDateFilter::getDateRange($filterFlag);
        }

        if(isset($dateRange)) {
            $query->whereBetween($jsonObj->column, $dateRange);
        }
    }

    public function updated($property)
    {
        if($property == 'dateRangeAppliedFilters' || $property == 'customStartDateFilter' || $property == 'customEndDateFilter') {
            $this->resetPagination();
        }
    }

    public function registerDateRangeFilters()
    {
        if($this->tableObject instanceof IDateRangeFilter) {

            $state = $this->tableObject->dateFilterSettings->getBitMask();
            $possibleValues = $this->dateRangeDescription($state);

            $this->filters
            ->add($this->tableObject->dateFilterSettings->databseColumnName)
            ->useLabel($this->tableObject->dateFilterSettings->showLabel)
            ->label($this->tableObject->dateFilterSettings->customLabel)
            ->with($possibleValues)
            ->as(FilterType::DATE_RANGE);
        }
    }


    /**
     * 
     * @param CommonDateFilter[] $flags
     * 
     */
    private function dateRangeDescription(int $state) : array
    {
        $possibleFilterValues = [];
        foreach ($this->commonDateFilters as $flag) {
            if(CommonDateFilter::hasFlag($state, $flag) && $flag != CommonDateFilter::ALL_RANGES){
                $possibleFilterValues[CommonDateFilter::getDateRangeDescription($flag)] = $flag->value;
            }
        }
        return $possibleFilterValues;
    }
}