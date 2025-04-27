<?php

namespace Mmt\GenericTable\Components;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use Mmt\GenericTable\Interfaces\IColumn;
use Traversable;

/**
 * @implements \Iterator<int, \Mmt\GenericTable\Components\Column>
 */
class ColumnCollection implements Iterator, Traversable
{
    private int $position = 0;

    private array $items = [];

    public int $count
    {
        get => count($this->items);
    }

    public function add(IColumn ...$column)
    {
        foreach ($column as $col) {
            $col->columnIndex = count($this->items);
            array_push($this->items, $col);
        }
        return $this;
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
    
    public function at(int $index): ?IColumn
    {
        return $this->items[$index] ?? null;
    }

    public static function make(IColumn ...$columns): ColumnCollection
    {
        return new static()->add(...$columns);
    }
}