<?php

namespace Mmt\GenericTable\Components;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mmt\GenericTable\Attributes\MappedRoute;
use Mmt\GenericTable\Enums\ColumnSettingFlags;
use Mmt\GenericTable\Interfaces\IColumn;
use Mmt\GenericTable\Interfaces\IColumnRenderer;
use Schema;
use Str;

final class Column implements IColumn, IColumnRenderer
{
    public int $settings = 0;

    public array $filters = [];

    public ?MappedRoute $mappedRoute = null;

    private ?Closure $formatterCallback;
    

    public function __construct(public string $columnTitle, public ?string $databaseColumnName = null)
    {
        if(empty($this->databaseColumnName)) {
            $this->databaseColumnName = Str::snake($columnTitle);
        }

        ColumnSettingFlags::addFlags($this->settings,
            ColumnSettingFlags::SORTABLE
        );
    }

    public function withSettings(ColumnSettingFlags ...$moreSettings)
    {
        if(count($moreSettings) === 0)
            $this->defaultColumnSettings();
        else {
            ColumnSettingFlags::addFlags($this->settings, ...$moreSettings);
        }

        return $this;
    }

    public function withSettingsIf(bool $condition, ColumnSettingFlags ...$moreSettings)
    {
        if($condition) {
            $this->withSettings(...$moreSettings);
        }
        return $this;
    }

    public function bindToRoute(MappedRoute $route)
    {
        $this->mappedRoute = $route;
        return $this;
    }

    private function withFilters()
    {

        return $this;
    }

    private function defaultColumnSettings()
    {
        ColumnSettingFlags::addFlags($this->settings,
            ColumnSettingFlags::SEARCHABLE,
            ColumnSettingFlags::SORTABLE,
            ColumnSettingFlags::TOGGLE_VISIBILITY
        );
    }
    

    public static function defaultCollection(Model $model) : ColumnCollection
    {
        $table = $model->getTable();
        $sqlColumnsInfo = Schema::getColumns($table);
        $items = collect(array_map(fn($e) => $e['name'], $sqlColumnsInfo));

        $columnsCollection = new ColumnCollection();

        // 1. All snake_case fields will be maped to spaced word. E.g: user_name will be mapped to User Name for html
        // 2. All fields with _id sufix will be omitted in html output
        $items->filter(fn($c) => !Str::endsWith($c,'_id'))->map(function($sqlColumn, $index) use(&$columnsCollection) {

            $columnsCollection->add(
                new self(Str::headline($sqlColumn), $sqlColumn)
                ->withSettings(ColumnSettingFlags::SORTABLE)
                ->withSettingsIf(
                    $index == 0,  
                    ColumnSettingFlags::SORTABLE,
                    ColumnSettingFlags::DEFAULT_SORT
                )
            );

        });
        
        // 3. The created_at field will be used as a default date filter #TODO
        // 4. The first database field will be used as the default sorteable column #TODO
        
        return $columnsCollection;
    }

    public static function resolveColumnBinders(IColumn $column, Model $rowModel)
    {
        $newParams = [];

        foreach ($column->mappedRoute->routeParams as $index => $param) {
            if(Str::startsWith($param, [':'])) {
                $bindedColumn = Str::substr($param, 1);
                $newParams[$index] = $rowModel->{$bindedColumn};
            }
            else {
                $newParams[$index] = $param;
            }
        }

        return $newParams;
    }

    public function isSorteable()
    {
        return ColumnSettingFlags::hasFlag($this->settings, ColumnSettingFlags::SORTABLE);
    }

    /**
     * Determines if settings has one of the following values:
     * ```
     * DEFAULT_SORT
     * DEFAULT_SORT_ASC
     * DEFAULT_SORT_DESC
     * ```
     * @return bool
     */
    public function isDefaultSort()
    {
        return ColumnSettingFlags::hasFlag($this->settings, ColumnSettingFlags::DEFAULT_SORT) ||
        ColumnSettingFlags::hasFlag($this->settings, ColumnSettingFlags::DEFAULT_SORT_ASC) ||
        ColumnSettingFlags::hasFlag($this->settings, ColumnSettingFlags::DEFAULT_SORT_DESC);
    }

    public function renderCell(Model $rowModel): string
    {
        if( isset($this->formatterCallback) ) {
            return $this->formatterCallback->__invoke($rowModel);
        }
        return self::cellValue($this, $rowModel);
    }

    public static function cellValue( IColumn $column, Model $rowModel )
    {
        if( isset($column->mappedRoute) ) {
            
            $route = self::createUrlFromMappedRoute($column, $rowModel);

            $linkLabel = $column->mappedRoute->label;

            if( $linkLabel == '' ) {
                if(self::columnIsRelationship($column->databaseColumnName)) {
                    $linkLabel = self::getNestedRelationValue($rowModel, $column->databaseColumnName) ?? '';
                }
                else {
                    $linkLabel = $rowModel->{$column->databaseColumnName} ?? '';
                }
            }

            return <<<HTML
                <a href = "$route">$linkLabel</a>
            HTML;
        }
        else if( self::columnIsRelationship($column->databaseColumnName) ) {
            return self::getNestedRelationValue($rowModel, $column->databaseColumnName) ?? '';
        }
        else {
            return $rowModel->{$column->databaseColumnName} ?? '';
        }
    }

    public function hookFormatter(Closure $callback) : self
    {
        $this->formatterCallback = $callback;
        return $this;
    }

    public static function createUrlFromMappedRoute(IColumn $column, Model $rowModel)
    {
        if( count($column->mappedRoute->routeParams) > 0) {
            $routeParams = Column::resolveColumnBinders($column, $rowModel);
            return route($column->mappedRoute->route, $routeParams);
        }
        return '##';
    }


    public static function getNestedRelationValue(Model $model, string $relationPath)
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

    public static function columnIsRelationship( IColumn|string $column )
    {
        if($column instanceof IColumn) {
            return Str::contains($column->databaseColumnName, '.');
        }
        return Str::contains($column, '.');
    }

    public static function columnIsNotRelationship(  IColumn|string $column )
    {
        return !self::columnIsRelationship($column);
    }
    

    /**
     * 
     * Returns the foreign key of a relationship
     * 
     */
    public static function relationshipForeignKey( IColumn|string $column, Model $model ) : string|null
    {
        $selection        = self::getRelationshipPath($column);
        $relationshipName = explode('.', $selection)[0];
        $relationInstance = $model->{$relationshipName}();

        if($relationInstance instanceof BelongsTo) {
            return $model->{$relationshipName}()->getForeignKeyName();
        }
        return null;
    }
    
    
    /**
     * 
     * Obtains the path given a full path relationship
     * For example you.are.intelligent returns you.are
     * 
     */
    public static function getRelationshipPath( IColumn|string $column ) : string
    {
        if($column instanceof IColumn) {
            $lastDotPosition = strrpos($column->databaseColumnName, '.');
            return substr($column->databaseColumnName, 0, $lastDotPosition);
        }
        else {
            $lastDotPosition = strrpos($column, '.');
            return substr($column, 0, $lastDotPosition);
        }
    }
    
    public static function getRelationshipPathAndColumn( IColumn|string $column ) : array
    {
        if($column instanceof IColumn) {
            $lastDotPosition = strrpos($column->databaseColumnName, '.');
            $relationship = substr($column->databaseColumnName, 0, $lastDotPosition);
            $column = substr($column->databaseColumnName,  $lastDotPosition +1, strlen($column->databaseColumnName));
            return [$relationship, $column];
        }
        else {
            $lastDotPosition = strrpos($column, '.');
            $relationship = substr($column, 0, $lastDotPosition);
            $column = substr($column,  $lastDotPosition +1, strlen($column));
            return [$relationship, $column];
        }
    }
}