<?php

namespace Mmt\GenericTable\Components;

use Illuminate\Database\Eloquent\Model;
use Mmt\GenericTable\Attributes\MappedRoute;
use Mmt\GenericTable\Enums\ColumnSettingFlags;
use Mmt\GenericTable\Enums\HrefTarget;
use Mmt\GenericTable\Interfaces\IColumn;
use Schema;
use Str;

final class Column implements IColumn
{
    public int $settings = 0;

    public array $filters = [];

    public string $bindedRoute = "";

    public array $routeParams = [];

    public string $linkLabel = "";

    public HrefTarget $target;
    

    public function __construct(public string $columnTitle, public ?string $databaseColumnName = null)
    {
        if(empty($this->databaseColumnName)) {
            $this->databaseColumnName = Str::snake($columnTitle);
        }

        $this->defaultColumnSettings();
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
        $this->bindedRoute = $route->route;
        $this->routeParams = $route->routeParams;
        $this->target = $route->target;
        $this->linkLabel = $route->label;

        return $this;
    }

    public function withFilters()
    {

        return $this;
    }

    private function defaultColumnSettings()
    {
        ColumnSettingFlags::addFlags($this->settings,
            ColumnSettingFlags::SEARCHABLE,
            ColumnSettingFlags::SORTEABLE,
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
                ->withSettings(ColumnSettingFlags::SORTEABLE)
                ->withSettingsIf(
                    $index == 0,  
                    ColumnSettingFlags::SORTEABLE,
                    ColumnSettingFlags::DEFAULT_SORT
                )
            );

        });
        
        // 3. The created_at field will be used as a default date filter #TODO
        // 4. The first database field will be used as the default sorteable column #TODO
        
        return $columnsCollection;
    }

    public function resolveBindedParams(Model $model) : array
    {
        if(count( $this->routeParams ) > 0)
        {
            $newParams = [];
            foreach ($this->routeParams as $index => $param) {
                if(Str::startsWith($param, [':'])) {
                    $bindedColumn = Str::substr($param,1);
                    $newParams[$index] = $model->{$bindedColumn};
                }
                else {
                    $newParams[$index] = $param;
                }
            }
            
            return $newParams;
        }

        return [];
    }

    public function isSorteable()
    {
        return ColumnSettingFlags::hasFlag($this->settings, ColumnSettingFlags::SORTEABLE);
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
    

    public function isHidden()
    {
        return ColumnSettingFlags::hasFlag($this->settings, ColumnSettingFlags::HIDDEN);
    }
}