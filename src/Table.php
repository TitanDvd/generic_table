<?php

namespace Mmt\GenericTable;


use Closure;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Component as LivewireComponent;
use Livewire\WithPagination;
use Mmt\GenericTable\Attributes\CellFormatter;
use Mmt\GenericTable\Attributes\ColumnSettings;
use Mmt\GenericTable\Attributes\MappedRoute;
use Mmt\GenericTable\Attributes\OnReorder;
use Mmt\GenericTable\Components\Column;
use Mmt\GenericTable\Components\TableFilterCollection;
use Mmt\GenericTable\Components\TableFilterItem;
use Mmt\GenericTable\Enums\ColumnSettingFlags;
use Mmt\GenericTable\Enums\FilterType;
use Mmt\GenericTable\Enums\PaginationRack;
use Mmt\GenericTable\Interfaces\{IActionColumn, IDragDropReordering, IEvent, IExportable,IGenericTable,IPaginationRack, IRowsPerPage, IBulkAction, ILoadingIndicator };
use Mmt\GenericTable\Support\DatabaseEvent;
use Mmt\GenericTable\Support\EventArgs;
use Mmt\GenericTable\Support\ExportEventArgs;
use Mmt\GenericTable\Support\ExportSettings;
use Mmt\GenericTable\Traits\WithBulkActions;
use Mmt\GenericTable\Traits\WithDateFilters;
use Mmt\GenericTable\Traits\WithMultiSelectionFilter;
use Mmt\GenericTable\Traits\WithSingleSelectionFilter;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Str;


class Table extends LivewireComponent
{
    use WithPagination,
        WithBulkActions,
        WithDateFilters,
        WithSingleSelectionFilter,
        WithMultiSelectionFilter;

    
    public int $rowsPerPage = 20;
    public int $sort = 0; // 1-ASC 2-DESC
    public bool $isReordering = false;
    public bool $useDragDropReordering = false;
    public array $rowsPerPageOptions = [20,40,60,80,100];
    public string $paginationTheme = 'bootstrap';
    public string $searchKeyWord = '';
    public string $sortedColumn = '';
    public array $searchByColumns = [];
    public string $tableClass = '';
    public bool $useModelAttributesAsColumns;
    public bool $componentInitialized = false;
    public string $pageName;
    public bool $useExport = false;
    public array $indexedRelationships = [];
    public bool $isActionColumnActive = false;
    public array $injectedArguments = [];
    public $listeners = ['refresh_generic_table' => '$refresh', 'refreshGenericTable' => '$refresh'];
    public bool $useCustomLoadingIndicator = false;

    
    final protected int $paginationRack = 0;
    final protected array $formatters = [];
    final protected int $actionColumnIndex = -1;
    final protected Closure|null $actionColumnViewCallback = null;
    
    
    /** @var ReflectionMethod[] */
    private array $reflectedMethods;
    private Model $model;
    private ReflectionClass $reflectedClass;
    private string $dragDropUseColumn = '';
    private string $onReorderCallbackMethod;
    private string $exportCallback;
    private LengthAwarePaginator $_tableItems;
    private TableFilterCollection $filters;
    

    final protected LengthAwarePaginator $tableItems
    {
        get 
        { 
            if(! isset($this->_tableItems))
            {
                $this->_tableItems = $this->execQuery();
            }

            return $this->_tableItems;
        }

        set (LengthAwarePaginator $itemsPaginations)
        {
            $this->_tableItems = $itemsPaginations;
        }
    }

    
    private IGenericTable|IActionColumn|IExportable|IBulkAction|ILoadingIndicator $tableObject
    {
        get {
            if(!isset($this->tableObject)) {
                $this->tableObject = new $this->tableClass;
            }
            return $this->tableObject;
        }
        set => $value;
    }
    

    /**
     * 
     * @internal
     * 
     */
    public function updatedRowsPerPage()
    {
        $this->resetPagination();
    }

    /**
     * 
     * @internal
     * 
     */
    public function updatedSearchByColumns()
    {
        if(count($this->selectedSearchColumn()) == 0)
        {
            $this->resetPagination();
            $this->resetSearch();
        }
    }

    /**
     * 
     * @internal
     * 
     */
    public function getFiltersProperty()
    {
        return $this->filters;
    }

    public function mount(string $table, array $args = [])
    {
        foreach ($args as $key => $value) {
            $this->injectedArguments[$key] = $value;
        }

        $this->tableClass = $table;
        
        $this->initializingComponent();
    }

    
    private function initializingComponent(bool $initRegisters = true)
    {
        if(!$this->tableObject instanceof IGenericTable) {
            throw new Exception('Class does not implements IGenericTable interface');
        }
        
        if(is_object($this->tableObject->model) && $this->tableObject->model instanceof Model) {
            $this->model = $this->tableObject->model;
        }
        else {
            $this->model = new $this->tableObject->model;
        }

        $this->useModelAttributesAsColumns = !isset($this->tableObject->columns);
        
        $this->reflectedClass = new ReflectionClass($this->tableObject);
        $this->pageName = strtolower($this->reflectedClass->getShortName());
        
        if($this->tableObject instanceof IPaginationRack) {
            $this->paginationRack = $this->tableObject->paginationRack;
        }
        else {
            $this->paginationRack = PaginationRack::BOTTOM->value;
        }

        if($this->tableObject instanceof IActionColumn) {
            $this->isActionColumnActive = true;
            $this->actionColumnIndex = $this->tableObject->actionColumnIndex;
            $this->actionColumnViewCallback = fn(Model $modelItem) => $this->tableObject->actionView($modelItem);
        }
       
        if($this->tableObject instanceof IDragDropReordering) {
            $this->useDragDropReordering = true;
            $this->dragDropUseColumn = $this->tableObject->orderingColumn ?? 'order';
            $this->registerOnDragDropOrderingCallback();
        }

        // Public properties will not be initialized again
        // as is livewire who maintains the state
        if($this->componentInitialized == false) {
            
            if($this->tableObject instanceof IEvent) {
                $this->resetPagination();
            }

            if($this->tableObject instanceof IRowsPerPage) {
                $this->rowsPerPage = $this->tableObject->rowsPerPage;
                $this->rowsPerPageOptions = $this->tableObject->rowsPerPageOptions;
            }

            if($this->tableObject instanceof IExportable) {
                $this->useExport = true;
            }

            if($this->tableObject instanceof ILoadingIndicator) {
                $this->useCustomLoadingIndicator = true;
            }

            $this->hasBulkActions = $this->tableObject instanceof IBulkAction;
        }

        $this->reflectedMethods = $this->reflectedClass->getMethods();
        $this->filters = new TableFilterCollection();
        
        if($this->useModelAttributesAsColumns == true) {
            $this->tableObject->columns = Column::defaultCollection($this->model);
        }
        
        $this->registerFormatters();
        $this->registerDateRangeFilters();
        $this->registerSingleSelectionFilter();
        $this->registerMultiSelectionFilters();

        $this->componentInitialized = true;
    }

    
    /**
     * 
     * @internal
     * 
     */
    public function render()
    {
        return view('generic_table::main');
    }
    
    
    private function execQuery(bool $paginate = true, bool $returnQueryBuilder = false): \Illuminate\Database\Eloquent\Builder|Builder|Collection|LengthAwarePaginator
    {
        $columnSelection = $this->makeSqlSelectionFromColumns();
        $modelTableName = $this->model->getTable();

        // Get the "with" relationship cases
        $relationships = $this->resolveRelationshipsFromColumnSelection($columnSelection);

        $this->indexedRelationships = $this->indexRelationships($columnSelection);
        
        $foreignColumnsSelection = $this->relationshipForeignKeysSelection();
        
        $query = DB::query()->from($modelTableName)->select(...$columnSelection, ...$foreignColumnsSelection);

        if(count($relationships) > 0) {
            $query = $this->model->with(...$relationships)->setQuery($query);
        }
        else {
            $query = $this->model->setQuery($query);
        }

        if($this->searchKeyWord != '') {
            
            $searchInColumns = $this->rebuildDbColumnNameWithRelation($this->searchByColumns);

            $query->orWhere(function($q) use($searchInColumns) {
                    
                foreach ($searchInColumns as $columnName) {
                    $slots = explode('.', $columnName);
                    $relatedColumn = $slots[count($slots) - 1];
                    $relation = str_replace('.'.$relatedColumn , '', $columnName);

                    if(Str::contains($columnName, '.')) {
                        $q->orWhereRelation($relation, $relatedColumn, 'LIKE', '%'. $this->searchKeyWord .'%');
                    }
                    else {
                        $q->orWhere($columnName,'like','%'. $this->searchKeyWord .'%');
                    }
                }

            });
        }
        

        if($this->tableObject instanceof IEvent) {
            
            $eventArgs = new DatabaseEvent(
                $query,
                $this->injectedArguments
            );

            $this->tableObject->dispatchCallback($eventArgs);
        }
        
        if($this->dragDropUseColumn != '' && $this->useDragDropReordering == true && $this->isReordering == true) {
            $this->sortedColumn = $this->dragDropUseColumn;
            $this->sort = 1;
        }
        
        if($this->sortedColumn != '')
            $query->orderBy($this->sortedColumn, $this->sort == 1 ? 'ASC' : 'DESC');

        

        $this->applySingleSelectionFilterToQuery($query);
        
        $this->applyMultiSelectionFilterToQuery($query);

        $this->applyDateFilterToQuery($query);
        
        if($paginate == true)
        {
            if($returnQueryBuilder == true){
                $pageNumber = $this->getpage($this->pageName);

                $query->forPage($pageNumber, $this->rowsPerPage);
                return $query->get();
            }
            else {
                return $query->paginate($this->rowsPerPage, ['*'], $this->pageName);
            }
        }
        else {
            if($returnQueryBuilder == false)
                return $query->get();
            return $query;
        }
    }


    private function rebuildDbColumnNameWithRelation($columns)
    {
        $newRelations = [];
        foreach ($columns as $column) {
            [$col, $can] = $this->rebuildDbRelationRecursive($column);
            if($can == true) {
                $newRelations[] = $col;
            }
        }
        return $newRelations;
    }
    

    private function rebuildDbRelationRecursive(array $column, string &$prevCol = '')
    {
        foreach ($column as $name => $can) {
            $prevCol .= '.'. $name;
            if(is_array($can)) {
                return $this->rebuildDbRelationRecursive($can, $prevCol);
            }
            else {
                return [substr($prevCol, 1), $can];
            }
        }
    }


    protected function getNestedRelationValue($model, $relationPath)
    {
        $relations = explode('.', $relationPath);
        foreach ($relations as $relation) {
            if (!$model) {
                return null;
            }
            $model = $model->$relation;
        }
        return $model;
    }


    /**
     * 
     * Index relationships in a separate array to be used
     * in render. Also, modifies removes the relationship from
     * the column selection
     * 
     */
    private function indexRelationships(array &$columnSelection) : array
    {
        $indexedRelationships = [];
        foreach($columnSelection as $idx => &$column) {
            if(Str::contains($column,'.')) {
                $indexedRelationships[$idx] = [
                    'index' => $idx,
                    'relationship' => $column,
                ];

                unset($columnSelection[$idx]);
            }
        }

        return $indexedRelationships;
    }

    private function relationshipForeignKeysSelection()
    {
        if(count($this->indexedRelationships ) > 0) {
            $rawSelection = [];
            foreach($this->indexedRelationships as $relationship) {
                $firstLevelRelationShip = explode('.', $relationship['relationship'])[0];
                $rawSelection[] = $this->model->{$firstLevelRelationShip}()->getForeignKeyName();
            }
            return $rawSelection;
        }

        return [];
    }


    private function resolveRelationshipsFromColumnSelection(array $columnSelection)
    {
        $reltionships = [];
        foreach($columnSelection as $column) {
            if(Str::contains($column, '.')) {
                $relation = substr($column, 0, strrpos($column, '.'));
                if(array_find($reltionships, fn($e) => $e == $column) == null) {
                    $reltionships[] = $relation;
                }
            }
        }
        return $reltionships;
    }

    
    private function makeSqlSelectionFromColumns() : array
    {
        if($this->useModelAttributesAsColumns == false)
        {
            return array_map(fn(Column $e) => $e->databaseColumnName, iterator_to_array($this->tableObject->columns));
        }

        return ['*'];
    }

    private function registerFormatters() : void
    {
        $classMethods = $this->reflectedClass->getMethods(ReflectionMethod::IS_PROTECTED|ReflectionMethod::IS_PUBLIC);

        foreach ($classMethods as $method) {
            
            $methodAttributes = $method->getAttributes(CellFormatter::class);
            if(count($methodAttributes) > 0)
            {
                $formatterAttribute = $methodAttributes[0];
                $bindedDbColumn = $formatterAttribute->getArguments()[0];

                $this->formatters[$bindedDbColumn] = $method->name;
            }
        }
    }

    /**
     * 
     * @internal
     * 
     */
    final protected function binder($methodName, Model $arguments) : string
    {
        return $this->tableObject->$methodName($arguments);
    }


    private function resolveColumnSettings(ReflectionProperty $property) : array|null
    {
        $propertyAttributes = $property->getAttributes(ColumnSettings::class);

        if(count($propertyAttributes) > 0) {
            return $propertyAttributes[0]->getArguments();
        }

        return null;
    }

    /**
     * 
     * @internal
     * 
     */
    final protected function showPaginationRackOnTop() : bool
    {
        return PaginationRack::hasFlag($this->paginationRack, PaginationRack::TOP);
    }

    /**
     * 
     * @internal
     * 
     */
    final protected function showPaginationRackOnBottom() : bool
    {
        return PaginationRack::hasFlag($this->paginationRack, PaginationRack::BOTTOM);
    }

    
    /**
     * 
     * @internal
     * 
     */
    public function sortColumn(string $columnName)
    {
        $this->sort = $this->sort == 1 || $this->sort == 0 ? 2 : 1;
        $this->sortedColumn = $columnName;
        
        // Forces system not to order anymore by drag and drop ordering column
        $this->dragDropUseColumn  = '';

        /**
         * This method only works if WithBulkAction trait is present
         * Updates the rows selection for bulk actions
         * @see \Mmt\GenericTable\Traits\WithBulkActions
         * 
         */
        $this->updateBulkSelection();
    }

    /**
     * 
     * Used by main blade template
     * @internal
     * 
     */
    final protected function resolveSearchColumns()
    {
        return array_filter(
            iterator_to_array($this->tableObject->columns), 
            fn(Column $column) => ColumnSettingFlags::hasFlag($column->settings, ColumnSettingFlags::SEARCHABLE)
        );
    }
    
    /**
     * 
     * @internal
     * 
     */
    public function search()
    {
        // This method fires the render and re-run query with search key word filled

        $this->resetPagination();
    }

    /**
     * 
     * @internal
     * 
     */
    public function resetSearch()
    {
        $this->searchKeyWord = '';
        $this->searchByColumns = [];
        $this->resetPagination();
    }

    /**
     * 
     * @internal
     * 
     */
    private function resetPagination()
    {
        $this->resetPage(
            $this->pageName
        );
    }

    /**
     * 
     * @internal
     * 
     */
    public function selectedSearchColumn()
    {
        return array_keys(array_filter($this->searchByColumns, fn($c) => $c));
    }

    /**
     * 
     * @internal
     * 
     */
    private function selectedMultiSelectionColumn(array $array)
    {
        return array_keys(array_filter($array, fn($c) => $c));
    }

    private function resolveColumnsMappedToRoute(ReflectionProperty $property) : MappedRoute|null
    {
        $propertyAttributes =  $property->getAttributes(MappedRoute::class);
        if(count($propertyAttributes) > 0){
            $reflectedAttr = $propertyAttributes[0];
            return $reflectedAttr->newInstance();
        }
        return null;
    }

    

    /**
     * 
     * @internal
     * 
     */
    public function filters()
    {
        return $this->filters;
    }

    /**
     * 
     * @internal
     * 
     */
    public function appliedFilters()
    {
        $appliedFilters = [];
        
        $this->multiSelectionFiltersInUse($appliedFilters);

        $this->singleSelectionFiltersInUse($appliedFilters);
        
        $this->dateFiltersInUse($appliedFilters);

        return $appliedFilters;
    }

    private function findFilterValueKey(string $column, $state)
    {
        foreach ($this->filters as $filterObject) {
            if($filterObject->column == $column) {
                foreach ($filterObject->possibleValues as $key => $value) {
                    if($value == $state)
                        return $key;
                }
            }
        }
        return $state;
    }

    /**
     * 
     * @internal
     * 
     */
    public function removeFilter($type, $column, $value)
    {
        if($type == 'single')
            $this->removeSingleSelectionFilter();
        if($type == 'multi')
            $this->removeFromMultiSelectionFilter($column, $value);
        if($type == 'date')
            $this->removeCustomDateRangeFilter();

        $this->updateBulkSelection();
        $this->resetPagination();
    }
    

    /**
     * 
     * @internal
     * 
     */
    public function internalExport()
    {
        $binder = fn($methodName, Model $item) => $this->binder($methodName, $item);

        return $this->tableObject->onExport(
            new ExportEventArgs(
                $this->tableObject->columns, 
                new ExportSettings(),
                $this->execQuery(false, true),
                $binder,
                $this->formatters
            )
        );
    }

    /**
     * 
     * @internal 
     * 
     **/
    public function actionIndex()
    {
        return isset($this->actionColumnIndex) ? $this->actionColumnIndex : $this->columns->count - 1;
    }
    

    protected function actionView(Model $modelItem)
    {
        return $this->actionColumnViewCallback->call($this, $modelItem);
    }

    /**
     * 
     * @internal
     * 
     */
    public function tableReOrderCompleted(int $newPosition, string $jsonData)
    {
        $callbackMakeReordering = false;
        $callbackMethodNameIsSetted = isset($this->onReorderCallbackMethod);

        $jsonData = json_decode($jsonData, true);
        $model = $this->model::find($jsonData['id']);
        $oldPos = $model->order;
        $page = $this->getPage(
            $this->pageName
        );
        
        $offset = $page > 1 ? ($this->rowsPerPage*$page)-$this->rowsPerPage : 0;
        $newPosition += 1 + $offset;

        if($callbackMethodNameIsSetted == true && method_exists($this,  $this->onReorderCallbackMethod)) {
            $callbackMakeReordering = $this->{$this->onReorderCallbackMethod}($newPosition, $oldPos, $model);
        }
        
        if($this->useDragDropReordering == true  && $callbackMakeReordering == false) {
            
            $modelPrimaryKey = $model->getKeyName();
            
            $tableName = $this->model->getTable();
            
            DB::update(<<<SQL
                UPDATE $tableName
                SET `{$this->dragDropUseColumn}` = $newPosition
                WHERE `$modelPrimaryKey` = {$model->$modelPrimaryKey}
            SQL);
            
            // Moving up
            if($oldPos > $newPosition){
                DB::update(<<<SQL
                    UPDATE $tableName
                    SET `{$this->dragDropUseColumn}` = `{$this->dragDropUseColumn}` + 1
                    WHERE `{$this->dragDropUseColumn}` BETWEEN $newPosition AND $oldPos AND `$modelPrimaryKey` != {$model->$modelPrimaryKey}
                SQL);
            }
            else {
                // Moving down
                DB::update(<<<SQL
                    UPDATE $tableName
                    SET `{$this->dragDropUseColumn}` = `{$this->dragDropUseColumn}` - 1
                    WHERE `{$this->dragDropUseColumn}` BETWEEN $oldPos AND $newPosition AND `$modelPrimaryKey` != {$model->$modelPrimaryKey}
                SQL);
            }
        }
    }
    

    private function registerOnDragDropOrderingCallback()
    {
        $methods = $this->reflectedClass->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if( count($method->getAttributes(OnReorder::class)) > 0 ) {
                $this->onReorderCallbackMethod = $method->name;
                break;
            }
        }
    }

    private function defaultColumnAndOrder(Column $column) : array
    {
        if($column->isDefaultSort() == true) {
            
            // If column is visible ...
            if(ColumnSettingFlags::hasFlag($column->settings, ColumnSettingFlags::HIDDEN) == false) {
                // ... automatically add sorteable settings
                ColumnSettingFlags::addFlag($column->settings, ColumnSettingFlags::SORTEABLE);
            }
            
            // Leave only the latest set of DEFAULT_SORT_xxx
            $defaultSort = ColumnSettingFlags::sanitizeSortFlags($column->settings);

            $isAscOrder = ColumnSettingFlags::hasFlag($defaultSort, ColumnSettingFlags::DEFAULT_SORT) ||
                        ColumnSettingFlags::hasFlag($defaultSort, ColumnSettingFlags::DEFAULT_SORT_ASC);
            return [
                $column->databaseColumnName,
                $isAscOrder == true ? 'asc' :'desc'
            ];
        }

        return [null, null];
    }

    public function hydrate()
    {
        $this->initializingComponent();
    }

    public function isSingleSelectionFilter(TableFilterItem $filter)
    {
        return $filter->type == FilterType::SINGLE_SELECTION;
    }

    public function isMultiSelectionFilter(TableFilterItem $filter)
    {
        return $filter->type == FilterType::MULTI_SELECTION;
    }
    
    /**
     * 
     * This method can be use as a proxy between table definition
     * and wire events from the views injected in generic table 
     * 
     */
    public function dispatcher($params)
    {
        if($this->tableObject instanceof IEvent) {
            $this->tableObject->dispatchCallback(
                new EventArgs($params)
            );
        }
    }

    public function genericSystemTableHandleOrdering()
    {
        $this->isReordering = !$this->isReordering;
    }

    /**
     * 
     * Remember, only wireable data can be injected
     * 
     */
    #[On('injectParams')]
    public function injectParams($params)
    {
        if(is_array($params)) {
            foreach ($params as $key => $value) {
                $this->injectedArguments[$key] = $value;
            }
        }
        else {
            $this->injectedArguments = $params;
        }
    }


    public function tableLoader()
    {
        if($this->useCustomLoadingIndicator) {
            return $this->tableObject->tableLoadingIndicatorView();
        }
        return view('generic_table::table_loader');
    }
}