<?php

namespace Mmt\GenericTable\Support;

use Iterator;
use Traversable;

/**
 * @implements \Iterator<int, FilterSettings>
 */
class FilterCollection implements Iterator, Traversable
{
    private int $position = 0;

    private array $items = [];

    public int $count
    {
        get => count($this->items);
    }

    public function __construct(FilterSettings ...$filter)
    {
        if(count($filter) > 0) {
            array_push($this->items, ...$filter);
        }
    }

    public function add(FilterSettings ...$filter)
    {
        array_push($this->items, ...$filter);
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

    public static function make(FilterSettings ...$filter): FilterCollection
    {
        return new static()->add(...$filter);
    }
}