<?php

namespace Mmt\GenericTable\Components;

use Mmt\GenericTable\Enums\FilterType;


/**
 * 
 * @method TableFilterItem with(array $possibleValues)
 * @method TableFilterItem with(string ...$possibleValues)
 * 
 * ```
 * Code example
 * 
 * <?php
 * ...->with(['Nike', 'Jordans', 'Addidas']);
 * ...->with('Nike', 'Jordans', 'Addidas');
 * ?>
 * ```
 * 
 */
class TableFilterItem
{

    public array $possibleValues;

    public private(set) FilterType $type;

    public private(set) string $customLabel;

    public private(set) bool $showLabel;

    public function __construct(public string $column, private TableFilterCollection|null $parentFilter)
    {
        $this->customLabel = '';
        $this->showLabel = false;
    }


    public function __call($method, $args)
    {
        if($method == 'with')
        {
            $mapedElements = [];
            $this->possibleValues = $args[0];
            
            array_walk($this->possibleValues, function($value, $index) use(&$mapedElements){

                if(is_array($value)) {
                    array_push($mapedElements, ...$value);
                }
                else {
                    array_push($mapedElements, $value);
                }

            });

            $f = $this->parentFilter->items;
            array_push($f, $this);
            $this->parentFilter->items = $f;
            
            return $this;
        }

        return $method($args);
    }

    
    public function as(FilterType $type): TableFilterItem
    {
        $this->type = $type;
        return $this;
    }
    
    public function add(string $column) : TableFilterItem
    {
        return $this->parentFilter->add($column);
    }

    public function useLabel(bool $use = true) : TableFilterItem
    {
        $this->showLabel = $use;
        return $this;
    }

    public function label(string $label) : TableFilterItem
    {
        $this->customLabel = $label;
        return $this;
    }
}