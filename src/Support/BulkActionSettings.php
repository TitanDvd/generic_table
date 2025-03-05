<?php

namespace Mmt\GenericTable\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class BulkActionSettings
{
    public function __construct(

        /**
         * Contains the selection of elements that would be used for bulk actions
         * If property isAllSelected is true this array will be empty.
         */
        public array $selection,

        /**
         * Indicates that all elements should be considered for bulk actions
         */
        public bool $isAllSelected,

        /**
         * Indicates that all elements except this elements should be use for bulk actions
         * If property isAllSelected is false this array will be set to empty
         */
        public array $exceptions,

        /**
         * A collection of the model used to build the table
         */
        private Builder|\Illuminate\Database\Eloquent\Builder $queryBuilder,

        private string $modelPrimaryKey,

        private Model $model

    ) { }

    /**
     * 
     * Return a collection of elements based on the selection for bulk actions.
     * This method executes the underlying query method. If you need to obtain
     * thousands of records without exaust memory, you should use getQueryBuilder
     * and handle the underlying query yourself as you need
     * 
     * @see self::getQueryBuilder
     * 
     */
    public function getSelectedModels(int $chunkSize = 0): Collection
    {
        $query = $this->getQueryBuilder();
        if($chunkSize > 0)
            return $this->model->setQuery($query)->get()->chunk($chunkSize);
        return $this->model->setQuery($query)->get();
    }

    /**
     * Returns the primary key of the model with its values
     */
    public function getSelectedIds() : array
    {
        return $this->getQueryBuilder()->pluck($this->modelPrimaryKey)->toArray();
    }

    public function getQueryBuilder() : \Illuminate\Contracts\Database\Query\Builder
    {
        $query = null;

        if($this->isAllSelected) {
            $query = $this->queryBuilder->whereNotIn($this->modelPrimaryKey, $this->exceptions);
        }
        else {
            $query = $this->queryBuilder->whereIn($this->modelPrimaryKey, $this->selection);
        }
        return $query;
    }
}