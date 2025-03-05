<?php

namespace Mmt\GenericTable\Components;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
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

    public function add(Column ...$column)
    {
        array_push($this->items, ...$column);
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
    

    public static function make(Column ...$columns): ColumnCollection
    {
        return new static()->add(...$columns);
    }
}