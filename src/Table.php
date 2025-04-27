<?php

namespace Mmt\GenericTable;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Component as LivewireComponent;
use Livewire\WithPagination;
use Mmt\GenericTable\Attributes\CellFormatter;
use Mmt\GenericTable\Attributes\ColumnSettings;
use Mmt\GenericTable\Attributes\MappedRoute;
use Mmt\GenericTable\Attributes\OnReorder;
use Mmt\GenericTable\Components\Cell;
use Mmt\GenericTable\Components\Column;
use Mmt\GenericTable\Components\TableFilterCollection;
use Mmt\GenericTable\Components\TableFilterItem;
use Mmt\GenericTable\Enums\{
    ColumnSettingFlags,
    DatabaseEventQueryState,
    FilterType,
    PaginationRack
};
use Mmt\GenericTable\Exceptions\NotImplementedException;
use Mmt\GenericTable\Interfaces\{
    IActionColumn, 
    IDragDropReordering, 
    IEvent, 
    IExportable,
    IGenericTable,
    IPaginationRack,
    IRowsPerPage,
    IBulkAction,
    ILoadingIndicator,
    IColumn,
    IColumnRenderer
};
use Mmt\GenericTable\Interfaces\ICellData;
use Mmt\GenericTable\Support\{
    DatabaseEvent,
    EventArgs,
    ExportEventArgs,
    ExportSettings
};
use Mmt\GenericTable\Components\Row;
use Mmt\GenericTable\Traits\WithBulkActions;
use Mmt\GenericTable\Traits\WithDateFilters;
use Mmt\GenericTable\Traits\WithMultiSelectionFilter;
use Mmt\GenericTable\Traits\WithSingleSelectionFilter;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;
use stdClass;
use Str;
use Closure;
use DB;
use Exception;
use Mmt\GenericTable\Exceptions\EmptyModelException;


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
    public bool $isActionColumnActive = false;
    public array $injectedArguments = [];
    public $listeners = ['refresh_generic_table' => '$refresh', 'refreshGenericTable' => '$refresh'];
    public bool $useCustomLoadingIndicator = false;
    public string $genericId;
    public string $cid;

    
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
        $this->cid = 'a'.uniqid();
        $this->genericId = 'generic_table_'.$this->cid;

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
        else if(empty($this->tableObject->tableSettings)) {
            throw new NotImplementedException('The property GenericTableSettings::$tableSettings is not implemented');
        }
        else if(empty($this->tableObject->tableSettings->model)) {
            throw new EmptyModelException();
        }
        
        if(is_object($this->tableObject->tableSettings->model) && $this->tableObject->tableSettings->model instanceof Model) {
            $this->model = $this->tableObject->tableSettings->model;
        }
        else {
            $this->model = new $this->tableObject->tableSettings->model;
        }
        
        $this->useModelAttributesAsColumns = !isset($this->tableObject->tableSettings->columns);
        
        $this->reflectedClass = new ReflectionClass($this->tableObject);
        $this->pageName = strtolower($this->reflectedClass->getShortName());
        
        if($this->tableObject instanceof IPaginationRack) {
            $this->paginationRack = $this->tableObject->paginationRack;
        }
        else {
            $this->paginationRack = PaginationRack::BOTTOM->value;
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
            $this->tableObject->tableSettings->columns = Column::defaultCollection($this->model);
        }

        if($this->tableObject instanceof IActionColumn) {
            $this->isActionColumnActive = true;
            $this->actionColumnIndex = $this->tableObject->actionColumnIndex;

            $this->actionColumnViewCallback = fn(Model|stdClass $modelItem) => $this->tableObject->actionView(
                Row::make($modelItem)
            );
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
    
    private function buildQuery(array $relationShips, Builder &$query)
    {
        $joined = [];
        $selectedColumns = [];
        $str = '';

        foreach ($relationShips as $relationshipPath => $columnInterface) {
            
            $relatedTableModel = $this->model;
            
            //! La variable column tiene que formar parte de la seleccion
            [$relationship, $column] = Column::getRelationshipPathAndColumn($relationshipPath);

            // La variable relationship aun puede contener una ruta de relaciones
            $relationshipSlots = explode('.', $relationship);

            foreach ($relationshipSlots as $relationshipMethodname) {
                
                $laravelRelationShipMethodResult = $relatedTableModel->{$relationshipMethodname}();

                if($laravelRelationShipMethodResult instanceof BelongsTo) {
                    // Related
                    $relatedTableModel = $laravelRelationShipMethodResult->getRelated();
                    $relatedTablePrimaryKeyName = $relatedTableModel->getKeyName();
                    $relatedTableName = $relatedTableModel->getTable();
                    
                    // Local
                    $foreignKey = $laravelRelationShipMethodResult->getForeignKeyName();
                    $currentTable = $laravelRelationShipMethodResult->getChild();
                    $currentTableName = $currentTable->getTable();
                    
                    $joinToStr  = "{$relatedTableName}.{$relatedTablePrimaryKeyName}";

                    if(!in_array($joinToStr, $joined)) {
                        $str .= "JOIN $relatedTableName ON $joinToStr = {$currentTableName}.{$foreignKey}\r\n";
                        $joined[]   = $joinToStr;
                        $query->leftJoin($relatedTableName, $joinToStr, "{$currentTableName}.{$foreignKey}");
                    }
                }
                else if($laravelRelationShipMethodResult instanceof HasOne) {
                    // Local
                    $localKey = $laravelRelationShipMethodResult->getForeignKeyName();
                    $currentTableModel = $laravelRelationShipMethodResult->getParent();
                    $currentTableName = $currentTableModel->getTable();

                    // Related
                    $relatedTableModel = $laravelRelationShipMethodResult->getRelated();
                    $relatedTablePrimaryKey = $relatedTableModel->getKeyName();
                    $relatedTableName = $relatedTableModel->getTable();

                    $joinToStr = "{$relatedTableName}.{$localKey}";
                    
                    if(!in_array($joinToStr, $joined)) {
                        $str .= "JOIN $relatedTableName ON $joinToStr = {$currentTableName}.{$relatedTablePrimaryKey}\r\n";
                        $joined[]  = $joinToStr;
                        $query->leftJoin($relatedTableName, $joinToStr, "{$currentTableName}.{$relatedTablePrimaryKey}");
                    }
                    
                }
            }

            if(isset($relatedTableName)) {
                $alias = Str::snake($columnInterface->columnTitle);
                $columnSelection = "{$relatedTableName}.{$column} as {$alias}";
                if(!in_array($columnSelection, $selectedColumns)) {
                    $selectedColumns[] = $columnSelection;
                }
            }
            
        }
        
        $query->addSelect($selectedColumns);
    }
    
    private function execQuery(bool $paginate = true, bool $returnQueryBuilder = false): \Illuminate\Database\Eloquent\Builder|Builder|Collection|LengthAwarePaginator
    {
        $columnSelection = $this->makeSqlSelectionFromColumns();

        $relationships = $this->columnsRelationships();
        
        $modelTableName = $this->model->getTable();
        
        $query = DB::query()->from($modelTableName)->select(...$columnSelection);
        
        $this->buildQuery($relationships, $query);

        $query = $this->model->newQuery()->from($query);

        if($this->searchKeyWord != '') {

            $query->orWhere(function($q) {
                    
                foreach ($this->searchByColumns as $columnIndex => $shouldUse) {
                    
                    if($shouldUse == true) {
                        $columnInterface = $this->tableObject->tableSettings->columns->at($columnIndex);
                        if($columnInterface->isRelationship()) {
                            $q->orWhere(Str::snake($columnInterface->columnTitle),'like', "%{$this->searchKeyWord}%");
                        }
                        else {
                            $q->orWhere($columnInterface->databaseColumnName,'like', "%{$this->searchKeyWord}%");
                        }
                        
                    }
                }

            });
        }
        
        if($this->dragDropUseColumn != '' && $this->useDragDropReordering == true) {
            $this->sortedColumn = $this->dragDropUseColumn;
            $this->sort = 1;
        }
        
        if($this->sortedColumn != '') {
            $query->orderBy($this->sortedColumn, $this->sort == 1 ? 'ASC' : 'DESC');
        }
        else {

            // Set the default sort
            foreach ($this->tableObject->tableSettings->columns as $column) {

                [$column, $order] = $this->defaultColumnAndOrder($column);

                if(isset($column, $order)) {
                    $this->sortedColumn = $column; 
                    $this->sort = $order;

                    $query->orderBy( $this->sortedColumn, $this->sort == 1 ? 'ASC' : 'DESC');
                    break;
                }
            }
        }

        $this->applySingleSelectionFilterToQuery($query);
        
        $this->applyMultiSelectionFilterToQuery($query);

        $this->applyDateFilterToQuery($query);

        if($this->tableObject instanceof IEvent) {

            $eventArgs = new DatabaseEvent(
                DatabaseEventQueryState::ENDS,
                $query,
                $this->injectedArguments
            );

            $this->tableObject->dispatchCallback($eventArgs);
        }

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


    public function relationshipSort(&$query, string $column)
    {
        [$relationshipPath, $column] = Column::getRelationshipPathAndColumn($column);

        $relationshipSlots = explode('.', $relationshipPath);
        $lastRelationTable = '';
        $relationsCount = count($relationshipSlots);
        
        $relationModel = $this->model;

        foreach ($relationshipSlots as $idx => $relation) {
            
            /**
             * @var BelongsTo
             */
            $related = $relationModel->{$relation}();
            $foreignOrJoinKey = '';
            
            
            
            if($related instanceof BelongsTo) {
                
                $foreignOrJoinKey = $related->getForeignKeyName();
                $relationModelId = $relationModel->getKeyName();
                
                $table = $related->getRelated()->getTable();
                
                $query->join($table, "{$relationModel->getTable()}.$foreignOrJoinKey", "{$table}.$relationModelId");
                
                $relationModel = $related->getRelated();
            }
            else {

            }

            if($idx == $relationsCount -1) {
                $lastRelationTable = $table;
            }
            
        }
        
        $query->orderBy("$lastRelationTable.$column", $this->sort == 1 ? 'ASC' : 'DESC');
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
    

    public function columnsRelationships()
    {
        $relationships = [];
        foreach ($this->tableObject->tableSettings->columns as $column) {
            if(Column::columnIsRelationship($column)) {
                $relationships[$column->databaseColumnName] = $column;
            }
        }
        return $relationships;
    }
    
    /**
     * Creates a selection for the Laravel Builder select method
     * If column has relationships we would need the foreign key
     * to be part of this selection
     * 
     */
    private function makeSqlSelectionFromColumns() : array
    {
        if($this->useModelAttributesAsColumns == false)
        {
            $selection = [];
            $table = $this->model->getTable();

            foreach ($this->tableObject->tableSettings->columns as $column) {

                /**
                 * 
                 * If the column has the EMPTY flag, the selection will
                 * fill it with arbitrary data
                 * 
                 */
                if(ColumnSettingFlags::hasFlag($column->settings, ColumnSettingFlags::EMPTY)) {
                    $selection[] = DB::raw('"" as "' . $column->databaseColumnName .'"');
                }
                else if(Column::columnIsNotRelationship($column)) {
                    $selection[] = "$table.$column->databaseColumnName";
                }
            }
            
            return $selection;
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
    final protected function binder($methodName, IColumn $column, Model $rowModel) : string
    {
        $iCell = Cell::make($column, $rowModel);
        $iRow = Row::make($rowModel);

        return $this->tableObject->$methodName($iCell, $iRow);
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
    public function sortColumn(int $columnIndex)
    {
        $iColumn            = $this->tableObject->tableSettings->columns->at($columnIndex);
        $useColumn          = $iColumn->isRelationship() ? Str::snake($iColumn->columnTitle) : $iColumn->databaseColumnName;
        $this->sort         = $this->sort == 1 || $this->sort == 0 ? 2 : 1;
        
        $this->sortedColumn = $useColumn;
        
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
            iterator_to_array($this->tableObject->tableSettings->columns), 
            fn(IColumn $column) => ColumnSettingFlags::hasFlag($column->settings, ColumnSettingFlags::SEARCHABLE)
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
            $this->removeSingleSelectionFilter($column, $value);
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
        $binder = fn($methodName, IColumn $column, Model $item) => $this->binder($methodName, $column, $item);
        
        $modelReflection = new ReflectionClass($this->model);
        $modelname = $modelReflection->getShortName();
        
        return $this->tableObject->onExport(
            new ExportEventArgs(
                $this->tableObject->tableSettings->columns, 
                new ExportSettings($modelname),
                $this->execQuery(false, true),
                $binder,
                $this->formatters,
                false,
                ''
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
    

    protected function actionView(Model|stdClass $modelItem)
    {
        return $this->actionColumnViewCallback->call($this, $modelItem);
    }

    /**
     * 
     * @internal
     * 
     */
    public function tableReOrderCompleted(array $jsonData)
    {
        $callbackMakeReordering = false;
        $callbackMethodNameIsSetted = isset($this->onReorderCallbackMethod);
        
        $elementId = json_decode($jsonData['element'], true)['id'];
        $siblingId = json_decode($jsonData['sibling'], true)['id'];
        $siblingIsLastElement = $jsonData['siblingIsLastElement'];
        
        $model = $this->model::find($elementId);
        $newPosModel = $this->model::find($siblingId);
        $oldPos = $model->{$this->dragDropUseColumn};

        /**
         * The new position sibling have a offset that I adjust
         * by taking the previous ordered model 
         */
        $newPosition = $newPosModel?->{$this->dragDropUseColumn};
        
        if($siblingIsLastElement == false && $oldPos < $newPosition)
            $newPosition -= 1;
        
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
            
            if($oldPos > $newPosition){
                // Moving up
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

    private function defaultColumnAndOrder(IColumn $column) : array
    {
        if(Column::columnHasDefaultSort($column)) {
            // Leave only the latest set of DEFAULT_SORT_xxx
            $defaultSort = ColumnSettingFlags::sanitizeSortFlags($column->settings);

            $isAscOrder = ColumnSettingFlags::hasFlag($defaultSort, ColumnSettingFlags::DEFAULT_SORT) ||
                        ColumnSettingFlags::hasFlag($defaultSort, ColumnSettingFlags::DEFAULT_SORT_ASC);
            return [
                $column->databaseColumnName,
                $isAscOrder == true ? 1 : 2
            ];
        }
        else {
            return [null, null];
        }
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
            return $this->tableObject->tableLoadingIndicatorView($this->genericId);
        }
        return view('generic_table::table_loader', ['genericId' => $this->genericId]);
    }

    public function columnIsHidden(IColumn $column)
    {
        return ColumnSettingFlags::hasFlag($column->settings, ColumnSettingFlags::HIDDEN);
    }

    public function columnIsSorteable(IColumn $column)
    {
        return ColumnSettingFlags::hasFlag($column->settings, ColumnSettingFlags::SORTABLE);
    }
    

    public function cellHtmlOutput(IColumn $column, Model $rowModel)
    {
        $formatter = null;
        
        $iCell = Cell::make($column, $rowModel);
        $iRow = Row::make($rowModel);
        
        if(isset($this->formatters[$column->databaseColumnName])) {
            $formatter = $this->formatters[$column->databaseColumnName];
        }
        
        if($column instanceof IColumnRenderer && $formatter == null) {
            return $column->renderCell($iCell, $iRow);
        }
        else if(isset($formatter)) {
            return $this->tableObject->{$formatter}( $iCell, $iRow );
        }
        else {
            
            return Column::cellValue($column, $iCell, $iRow);
        }
    }
    
    private function implementCellInterface($model, $fqcn, IColumn $column) : ICellData
    {
        $cell = new $fqcn;
        $cell->index = $column->columnIndex;
        $cell->value = $model->{$column->databaseColumnName};

        return $cell;
    }

    private function implementRowInterface($model, $fqcn, IColumn $column)
    {
        $iRow = new $fqcn;

        $objectRef = new ReflectionObject($iRow);
        $props = $objectRef->getProperties(ReflectionProperty::IS_PUBLIC);

        if($model instanceof stdClass) {
            foreach ($props as $prop) {
                $propVal = $model->{$prop->name} ?? null;
                if($propVal !== null) {
                    $iRow->{$prop->name} = $model->{$prop->name};
                }
            }
        }
        else if($model instanceof Model) {
            foreach ($props as $prop) {
                $proVal = $model->{$prop->name} ?? null;
                if($proVal !== null) {
                    $iRow->{$prop->name} = $model->{$prop->name};
                }
            }
        }

        $iRow->origin = $model;

        return $iRow;
    }

    private function inferImplementationTypeParams(ReflectionMethod $method) : array
    {
        $params = $method->getParameters();
        $fqcnArr = [];

        foreach ($params as $param) {
            $fqcnArr[] = $param->getType()?->__tostring();
        }

        return $fqcnArr;
    }
}