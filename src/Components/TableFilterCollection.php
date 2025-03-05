<?php

namespace Mmt\GenericTable\Components;

/**
 * @implements \Iterator<int, \Mmt\GenericTable\Components\TableFilterItem>
 */
class TableFilterCollection implements \Iterator
{
    private int $position = 0;
    
    /**
     * 
     * @var TableFilterItem[]
     * 
     */
    public array $items = [];

    public int $count
    {
        get => count($this->items);
    }

    public function add($column)
    {
        
        return new TableFilterItem($column, $this);
    }

    public function current(): mixed
    {
        return $this->items[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->items);
    }
}