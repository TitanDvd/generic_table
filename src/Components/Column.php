<?php

namespace Mmt\GenericTable\Components;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mmt\GenericTable\Attributes\MappedRoute;
use Mmt\GenericTable\Enums\ColumnSettingFlags;
use Mmt\GenericTable\Interfaces\ICellData;
use Mmt\GenericTable\Interfaces\IColumn;
use Mmt\GenericTable\Interfaces\IColumnRenderer;
use Mmt\GenericTable\Interfaces\IRow;
use Mmt\GenericTable\Interfaces\IRowData;
use Mmt\GenericTable\Support\Row;
use Mmt\GenericTable\Traits\WithColumnBuilder;
use Schema;
use stdClass;
use Str;

final class Column implements IColumn, IColumnRenderer
{
    use WithColumnBuilder;

    public int $settings = 0;

    public array $filters = [];

    public ?MappedRoute $mappedRoute = null;

    public int $columnIndex;

    public function __construct(public string $columnTitle, public ?string $databaseColumnName = null)
    {
        if(empty($this->databaseColumnName)) {
            $this->databaseColumnName = Str::snake($columnTitle);
        }

        // Exportable by default
        ColumnSettingFlags::addFlag($this->settings, ColumnSettingFlags::EXPORTABLE);
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

    public static function resolveColumnBinders(IColumn $column, IRowData $rowModel)
    {
        $newParams = [];

        foreach ($column->mappedRoute->routeParams as $index => $param) {
            if(Str::startsWith($param, [':'])) {
                $bindedColumn = Str::substr($param, 1);
                $newParams[$index] = $rowModel->origin->{$bindedColumn};
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

    public static function columnHasDefaultSort(IColumn $column)
    {
        return ColumnSettingFlags::hasFlag($column->settings, ColumnSettingFlags::DEFAULT_SORT_ASC) ||
                ColumnSettingFlags::hasFlag($column->settings, ColumnSettingFlags::DEFAULT_SORT_ASC) ||
                ColumnSettingFlags::hasFlag($column->settings, ColumnSettingFlags::DEFAULT_SORT_DESC);
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

    public function renderCell(ICellData $cell, IRowData $row): string|null
    {
        if( isset($this->formatterCallback) ) {
            return $this->formatterCallback->__invoke($row);
        }
        return self::cellValue($this, $cell, $row);
    }

    public static function cellValue( IColumn $column, ICellData $cell, IRowData $row ) : string|null
    {
        if( isset($column->mappedRoute) ) {
            
            $route = self::createUrlFromMappedRoute($column, $row);

            $linkLabel = $column->mappedRoute->label;

            if( $linkLabel == '' ) {
                $linkLabel = $cell->value;
            }

            return <<<HTML
                <a href = "$route">$linkLabel</a>
            HTML;
        }
        else {
            return $cell->value;
        }
    }

    public static function createUrlFromMappedRoute(IColumn $column, IRowData $rowModel)
    {
        if( count($column->mappedRoute->routeParams) > 0) {
            $routeParams = Column::resolveColumnBinders($column, $rowModel);
            return route($column->mappedRoute->route, $routeParams);
        }
        return '##';
    }

    public function isRelationship() : bool
    {
        return self::columnIsRelationship($this);
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
    public static function relationshipForeignKey( IColumn|string $column, Model|stdClass $row ) : string|null
    {
        $selection        = self::getRelationshipPath($column);
        $relationshipName = explode('.', $selection)[0];
        $relationInstance = $row->{$relationshipName}();

        if($relationInstance instanceof BelongsTo) {
            return $row->{$relationshipName}()->getForeignKeyName();
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