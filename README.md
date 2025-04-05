

# Generic Table: Laravel+Livewire package to automatize HTML tables

## Introduction
Generic Table is a package that makes working with tables in Laravel + Livewire easier. It was developed with performance in mind. The main reason behind the project is working with the interface design pattern to segregate responsibilities. From my perspective, table logic should be in its own place.


## Requirements
1. PHP <b>8.4</b>
2. Laravel >= 11.x
3. Livewire >= 3.x
4. Bootstrap 5.x

## How to install
`composer require mmt/generic_table`

## Features
- Drag and drop rows to simplify reordering. (using [Dragula Js][1])
- Classic column sorting.
- Bind the entire column to a laravel route.
- Create action columns. Customize them using blade views
- Filter by mutually exclusive or inclusives values.
- Search by any column
- Use relationships on columns to link the column with a relationship value.
- And more...

## Basic Usage
### First, the `Generic Table Definition`
With the following code snippet your are creating the table definition that you
will later will pass to the blade directive `@generic_table()`.
```php
<?php

namespace App\Tables;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Mmt\GenericTable\Components\ColumnCollection;

class ProductTable implements IGenericTable
{
    public Model|string $model = Product::class;
    
    public ColumnCollection $columns;
}
```

### Second, the livewire component
Now, create an accessible livewire component...

```php
// ...
// Component render method
public function render()
{
    return view('my.livewire.component-view', ['table' => \App\Tables\ProductTable::class]);
}
// ...
```
Then, in the view use the `@generic_table()` directive as follows:
```html
<div>
    @generic_table($table)
</div>
```

And that's it!

It's clear that the more features you need, the more configurations you will need. By default `generic_table` will detect that if no column definition was made, then: 
1. All attrbutes in the model are intended to be public. 
2. Attributes with the _id suffix will be omitted from the public view.
3. All columns are searchables
4. Nothing can be exported
5. Drag and drop sorting is disabled
6. All columns are sorteable

## Interfaces
The interface design pattern is the core of the project.
By using of several or all of the available interfaces you will be able to control
every aspect of the generic table engine. From here I will try to give you a datailed
break down of all interfaces that exists at the moment.

- [IGenericTable](#igenerictable)
- [IBulkAction](#ibulkaction)
- [IDateRangeFilter](#idaterangefilter)
- [IColumnFilter](#isingleselectionfilter)
- [IRowsPerPage](#irowsperpage)
- [IPaginationRack](#ipaginationrack)
- [IActionColumn](#iactioncolumn)
- [IDragDropReordering](#idragdropreordering---powered-by-dragula-js)
- [IEvent](#ievent)
- [IExportable](#iexportable)
- [ILoadingIndicator](#iloadingindicator)
- [IColumn and IColumnRenderer](#icolumn-and-icolumnrenderer)
	- [IColumn](#icolumn)
	- [IColumnRenderer](#icolumnrenderer)

## IGenericTable
This is the main interface. Every class table you want to define must implement the
`IGenericTable` interface. This interface has two property declarations.
1. `Model|string $model`. This property defines the model from which the engine will extract all the data from database, perform filters or searches.
2. `ColumnCollection $columns`. This property defines the columns that will be used in the html output

```php
// Example

$this->model = new Product();

$this->columns = ColumnCollection::make(
    new Column('Id'),
    new Column('Description'),
    new Column('Price'),
    new Column('Stock')
);
```
The first argument of `Column` constructor is a string in representing the the column label, the second parameter is the `database column name` but, if there is no second argument, the engine will use the `snake case` of the column label for the `database column name`. For example `SubDepartment` will be mapped to `sub_department` case using the `Str::snake()` method. You can also use defined relationship columns. For example:
```php
$this->columns = ColumnCollection::make(
	new  Column('Id'),
	new  Column('Description'),
	new  Column('Price'),
	new  Column('Stock'),
	new  Column('Department', 'subDepartment.department.name'),
	new  Column('SubDepartment', 'subDepartment.name'),
);
```
Some times you need to dinamically, bind the entire column to a laravel route. In that case you can configure the column as follows:
```php
$this->columns = ColumnCollection::make(
	new  Column('Id'),
	new  Column('Name')->bindToRoute(
		new MappedRoute('product_details', ['product_id' => ':id'], 'See Product Details')
	),
	new  Column('Description'),
	new  Column('Price'),
	new  Column('Stock'),
	new  Column('Department', 'subDepartment.department.name'),
	new  Column('SubDepartment', 'subDepartment.name'),
);
```
**`MappedRoute`** takes its as first argument a defined ***(existing)*** route name. The second argument would be the parameters expected by that route and here's a note. See how the route parameters are set...
`['product_id' => ':id']` . In order for the engine to be able to determine what value to pass to the route, you need to use a **`:binder`**. This tells the system what `database column value` to use in the row for the route work as expected. The third argument is the link label. If you leave the label empty, the system will use the value of that cell to represent the link label. The last argument is `HrefTarget $target` an `enum` to help you set the `target` attribute of the `<a>` tag.

If you need to hide a column for some reason, here is how: Use `ColumnSettingsFlag`.
In the example below you will find some other useful flags for column settings.
```php
$this->columns = ColumnCollection::make(
	new  Column('Id')->withSettings(
		ColumnSettingFlags::HIDDEN
	),
	new  Column('Description')->withSettings(
		ColumnSettingFlags::SEARCHABLE,
		ColumnSettingFlags::EXPORTABLE,
		ColumnSettingFlags::SORTEABLE,
	)->bindToRoute(
		new MappedRoute('product_details', ['product_id' => ':id'], 'See Product Details')
	),
	new  Column('Price'),
	new  Column('Stock')->withSettings(
		ColumnSettingFlags::DEFAULT_SORT_ASC
	),
	new  Column('Department', 'subDepartment.department.name'),
	new  Column('SubDepartment', 'subDepartment.name'),
);
```
----
```
Important!:

The column collection is a representation of a SQL selection. 
That is, if you only have 3 columns in your collection, 
that's all the selection Eloquent will perform. Engine will automatically adds
foreign keys but they won't be part of the model passed in callbacks or implemented 
interface methods.
```
### The empty column
An empty column is a special setting  that forces column to not be used in the selection query as existing column but column with an empty value. You just need to be sure that this column name isn't already used in the component. For example:
```php
new  Column('Bird')->empty()
```
The above example will set up a column in the HTML output called "Bird," with all its cells empty. In the "Row Model," you'll have something like `"bird" => ""`. This way, you can use this column however you like without referencing an existing column and format its value to your liking. Formatters are still required, though, as you don't want an empty column (most of the time). This is to avoid referencing an existing column that you don't need to be visible.

## IBulkAction

The `IBulkAction` interface has one property declaration which is `BulkActionCollection $bulkActionCollection`. Its goal is to define the bulk action methods or group of methods. Group of methods can have nested groups. The html output will be a dropdown menu with nested dropdowns menus if needed in the up-left-corner of the table.
```php
// Basic

$this->bulkActionCollection = BulkActionCollection::make(
    BulkAction::make('100:3 Boost Fund', fn($e) => $this->ProcessMassiveMarketing($e))
);

// Complex

$this->bulkActionCollection = BulkActionCollection::make(
    BulkActionGroup::make('Emails', 
        BulkActionGroup::make('FxLive', 
            BulkActionGroup::make('Marketing',
                BulkAction::make('100:1 Boost Fund', fn($e) => $this->ProcessMassiveMarketing($e)),
                BulkAction::make('100:2 Boost Fund', fn($e) => $this->ProcessMassiveMarketing($e)),
                BulkAction::make('100:3 Boost Fund', fn($e) => $this->ProcessMassiveMarketing($e)),
            ),
            BulkAction::make('100:3 Boost Fund', fn($e) => $this->ProcessMassiveMarketing($e)),
        )
    )
);

// ...

public  function  ProcessMassiveMarketing(BulkActionSettings  $bulkActionSettings)
{
	// ...
}
```
Normally, you would prefer to handle bulk actions in a Laravel Job but, if you need to make some processing on the main thread `BulkActionSettings` gives you some useful tools. Once the execution reaches the callback you will have access to the query builder, and of course, for performance reasons, in this point, the system will avoid querying the database, so is a developer's job but but you should fear not because obtaining the selected values is very easy. 
1. Use `$bulkActionSettings->getQueryBuilder();` if you wish to obtain only the query builder **with** the records to be process in the where clause. That way, when you executes the query at any time, it will give you what you select.
2. If you have a decent (lite) amount of data you can use `$bulkActionSettings->getSelectedModels()`... but keep in mind that this guy is a dragon of resources. This method will also provide all the selected data.
3. When you use `$bulkActionSettings->getSelectedIds();` all that you will be obtaining is the result of executing `$this->getQueryBuilder()->pluck($this->modelPrimaryKey)->toArray();`

## IDateRangeFilter
This interface has the property declaration `DateFilterSettings $dateFilterSettings`. Note that first argument of the class `DateFilterSettings` is the column to be used as the filterable date range column. The purpose is to gives the users a set of common labeled dates ranges from which the user can choose one of them. As you can imagine, each common date range has its own predefined `DateTime` range. See the example below. <br>
`Note: Labels descriptions cannot be modified by any interface at this time`

```php
// Example

$this->dateFilterSettings = new DateFilterSettings('created_at', 
    CommonDateFilter::LAST_2_MONTHS,
    CommonDateFilter::LAST_3_MONTHS
);
```

Additionally, you can specify the `CommonDateFilter::CUSTOM_RANGE` case to instruct the engine to allow the user to enter a custom date range from the HTML date input. Or you can use `CommonDateFilter::ALL_RANGES` case to specify that the engine must render all options included `CommonDateFilter::CUSTOM_RANGE` case.

## IColumnFilter
This interface has the FilterCollection property declaration $filters. It can contain a collection of two types of filters: SingleFilter or MultiFilter. As the name suggests, one allows you to configure a filter for a single value at a time, and the other for multiple values ​​at once:
```php
// Example

$this->filters = new  FilterCollection(
	SingleFilter::make('status', [
		'Out of stock' => 'out_of_stock',
		'Discontinued' => 'discontinued',
		'Available' => 'available'
	]),

	MultiFilter::make('name', [
		'Client-1' => 'laborum 505',
		'Client-2' => 'atque 38829',
		'Client-3' => 'rem 61603388'
	])
);
```
The `SingleFilter` and `MultiFilter` classes inherit from `FilterSettings`, so they both behave identically. The `::make($sqlColumn, $possibleValues)` constructor and method receive the column to filter as the first argument, and the second, an array of the possible values ​​to filter.
## IRowsPerPage
If you need to change the default behavior of the rows displayed per page or explicitly set the initial value of the rows per page you should implement the `IRowsPerPage` interface. The property `$rowsPerPage` will set the initial set of rows displayed and `$rowsPerPageOptions` property will overwrite the `Rows per page` options in the html output.

```php
// Example

public int $rowsPerPage = 10;
public array $rowsPerPageOptions = [10,20,40,60,80];
```

## IPaginationRack
To overwrite the default behavior of the pagination section of the table, you must implement the `IPaginationRack` interface as follows:

```php
// Example

public int $paginationRack = 0;

public function __construct()
{
    // Display the pagination on top and bottom
    PaginationRack::addFlags($this->paginationRack, PaginationRack::TOP, PaginationRack::BOTTOM);
}
```

## IActionColumn
Usually, you need to use a column as a container of buttons that control some of the actions in the row. This is a common case of an `action column`. This interfaces allows you to use one column with this purpose.

```php
// Example

public int $actionColumnIndex = -1;

public function actionView(Model $item): \Illuminate\View\View
{
    return view("tables_action_views.edit_delete_details", ['productId' => $item->id]);
}
```

The `public int $actionColumnIndex` property defines the position at which the column should be rendered. If the value is set to `-1` the engine will always set the action column as the last column rendered. By implementing the `public function actionView(Model $item): \Illuminate\View\View` you are telling the engine which view to use to render each set of controls for each cell in the column. The argument to the `actionView` method is an instance of the model and method must return a `\Illuminate\View\View` object.

[1]: https://bevacqua.github.io/dragula/
[idragdropreordering]:#idragdropreordering---powered-by-dragula-js

## IDragDropReordering - (powered by [Dragula JS][1])
The generic table engine helps you to sort rows manually. When you implement this interface you will be able to grab any rendered row and drop it wherever you need (inside the tbody). **For this case, the engine needs a column sorted from 1 to N** where **N** is the last row count. Let's say you have 5 records in the Product's table. The table needs to have a `order` column where the `order` value is from 1 to 5. The engine will not set the order for you and you need to keep this in mind when you inert new records. The interface declares only `public string $orderingColumn` property. If you implement this interface and leave the property unset, the engine will use the `order` column by default and if you don't have an existing column called `order` horrors will be seen. By default this column will force the system to use this column as a default sorted column. With the following code you will enable manual ordering using drag and drop
```php
// Example

class TableWithDragDropOrdering implements IGenericTable, IDragDropReordering
{
    public Model|string $model = Product::class;
    
    public ColumnCollection $columns;

    public string $orderingColumn = 'order';
    
    // ...
}
```
### OnReorder Attribute
Now, if you need to implement your own reordering method you could use the `OnReorder` attribute on a method. For example:
```php
#[OnReorder]
public function onReorderCallback(int $newPosition, $oldPosition, Model $model)
{
    /**
     * If method exists, it should return TRUE to indicate to the subsystem that
     * this method will handle the reording.
     * If you do not explicitly return boolean, the subsystem will use FALSE as a default return value
     */
    
    return false;
}
```

If the method exists, it should return `TRUE` to indicate to the subsystem that this method will handle the reording. If you do not explicitly return boolean, the subsystem will use FALSE as a default return value and will handle the reording ignoring your custom handler. When you move a record from postion **X** to position **Y** the **X** position is called the ***old position*** and ***Y*** position is called the ***new position***, and as you can understand, the `$model` is the moved element.

## IEvent
Use it when you need to capture some of the internal events of the engine and perform custom calculations or modify some thing at your convenience. The interface declares the method `public function dispatchCallback(EventArgs $arguments): void`.

### Times where `dispatchCallback` is fired:
1. When database query is about to be generated. The `dispatchCallback` receives a child class of `EvntArgs` called `DatabaseEvent` which is fired only in the initial state of the query builder.
2. When `dispatcher` is invoked from within `generic table`. Imagine you need to rise a callback event through the generic table for some reason, mainly to maintain organized logic, well, this is the way. You manually make a `wrie:click = "dispatcher( {object} )"` from an `action column control` for example:


```html
// Example of action column
// edit_delete_details.blade.php

<div>
    <a href="" wire:click.prevent = "$dispatch('edit', {productId:{{ $productId }}})">Edit</a>
    
    <!-- Here you can rise the default EventArgs in your table definition side -->
    <a href="" wire:click.prevent = "dispatcher({productId: {{ $productId }}})">Details</a>
    
    <a href="">Delete</a>
</div>
```

That way, you can structure your tables logic around the table class definition (as it should be) and not the livewire component. Of course, some times you will need to fire an event directly to your livewire component and a `$dispatch` from livewire is fine.

Another use case for this interface is to be used as a complement of `param injection`. The `param injection` is nothing but `generic table`that listens to a particular event called `injectParams` where you can pass arbitrary data that will be exposed in any `dispatchCallback`

Let's say you have defined the options for a navigation tab in your Livewire component and each tab should filter the generic table with some arbitrary or defined value. By using this interface in combination with `param injection` you can set the initial state of the query and have the selected tab fire the `injectParams` event on every update to the Livewire model. Let's look at a quick example:

#### Table definition:
```php
namespace App\Tables;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Mmt\GenericTable\Interfaces\IEvent;
use Mmt\GenericTable\Components\ColumnCollection;
use Mmt\GenericTable\Components\Column;

class TableWithFilters implements IGenericTable, IEvent
{
    public Model|string $model = Product::class;

    public ColumnCollection $columns;
    
    public function __construct()
    {
        $this->columns = ColumnCollection::make(
            new Column('Id'),
            new Column('Description'),
            new Column('Price'),
            new Column('Stock')
        );
    }
    
    
    public function dispatchCallback(EventArgs $arguments): void
    {
        if($arguments instanceof DatabaseEvent)
        {
            if($arguments->injectedArguments['tabView'] == 1)
                $arguments->builder->where('status', 'discontinued');
            else
                $arguments->builder->where('status', 'available');
        }
    }
}
```

As you can see from previous example the `dispatchCallback` implementation is prepare to receive `DatabaseEvent`. When that happens, the livewire component´s selected tab view can be used to controo the query behavior. But, there is no initial state for `$arguments->injectedArguments['tabView']` yet...

#### Livewire Component:

```php
<?php

namespace App\Livewire\Examples;

use App\Tables\TableWithFilters;
use Livewire\Component;

class TableWithFiltersComponent extends Component
{
    public int $tab;

    public function mount()
    {
        $this->tab = 1;
    }

    public function render()
    {
        return view('livewire.examples.table-with-filters-component', [
            'table' => TableWithFilters::class
        ])
        ->extends('components.layouts.app')
        ->section('content');
    }

    public function updatedTab($val)
    {
        $this->dispatch('injectParams', ['tabView' => $val]);
    }
}

// ... and view ...
```

```html
<div class="container-fluid">
    <div class="row">
        <div class="col-auto">
            <button wire:click = "$set('tab', 1)" class="w-100 btn btn-{{ $tab == 1 ? 'primary' : 'light' }}">Discontinued</button>
        </div>
        <div class="col-auto">
            <button wire:click = "$set('tab', 2)" class="w-100 btn btn-{{ $tab == 2 ? 'primary' : 'light' }}">Available</button>
        </div>
    </div>

    @generic_table($table, ['tabView' => $tab])
    
</div>
```

The `@generic_table` directive accepts two arguments. The first one is the `FQCN` of the table definition while the second is an array of possible arguments. All the arguments passed to the `generic table` component initialization will be exposed as a part of the event args in the `dispatchCallback` method implementation. In the above example you can see how by initializing the `generic table` with predefined values and thanks to generic table engine that exposes those values in the `dispatchCallback` we have the hability to set an initial state in the query builder and with the help of `injectParams` event we can update the `tabView` array key value to "keep track" of the the nav-tab filter

## IExportable
If you need export all queried registers you can use `IExportable` interface which declares the `public function onExport(ExportEventArgs $args) :  BinaryFileResponse|Response` method. When you implement this interface a `bootstrap warning button with a SVG cloud` appears in the top-right corner of the table. The system does not handle the export for you, but it makes it much easier. Once you have the `public function onExport(ExportEventArgs $args) : BinaryFileResponse|Response` implemented you only needs to `return $args->export();`

```php
// Example

public function onExport(ExportEventArgs $args) : BinaryFileResponse|Response
{
    $args->settings->fileName = 'my_products';
    return $args->export();
}
```

The `ExportEventArgs` have some interesting points worth explaining. Once the execution reaches the `onExport` method you have some options to handle. By default, the engine will set a timestamp on the exported file name like `date('YmdHis')`. So, `my_products` may end up  in `my_products_20250310220005`, but if you don't need that you can set `appendTimeMarkToFilename` to `false` like:
```php
public function onExport(ExportEventArgs $args) : BinaryFileResponse|Response
{
    $args->settings->fileName = 'my_products';
    $args->settings->appendTimeMarkToFilename = false;
    return $args->export();
}
```

Also, if you don't use a `filename`, the system will assign the model name in the Snake Case version. So, for the `MyProducts` model, the filename will be `my_products_<timestamp>.[extension]`.

### Deadlocks
Once the execution reaches `onExport` no query has been performed to improve performance. If the dataset obtained from the query execution is relatively short, you may have no problem calling the `$args->export();` method directly, but when 300K records come into play, things get heavy for the server and PHP kills the fun... rightly so. If you take a look at the `ExportEventArgs $args` the `generic table` offers you the `Eloquent\Builder` to manage the results as you need, but with all the filters applied if there are any. This way (custom handling) you are able to create a Laravel Job at this moment, save the file and send it by email or using another channel you have in mind... but! deadlocks can strike again and that is why I will always recommend doing exports using chunks or with limited amount of data.

### The Maatwebsite\Excel package
If you have installed the [Maatwebsite\Excel package](https://docs.laravel-excel.com/3.1/getting-started/) the `generic table` will use a predefinded template to export the data using this library, otherwise the exported file will be a `csv` file.

## ILoadingIndicator
This interface is useful only when you need to change the behavior of the loading indicator. When you perform some action inside the `generic table` component there is a Livewire hook waiting for the `request`. The loading indicator appears when the request is initiated and is hidden when the `requests` have been answered. To give you full control over the view you need to specify a blade view to serve as the loading indicator and the engine will toggle the CSS property `display: block|none` using the `id` attribute of the html tag which should be called `generic_table_loader`.
See the example:

```php
// Example in table definition

public function tableLoadingIndicatorView(string  $genericId): \Illuminate\View\View
{
    return view('custom-loader');
}
// and view ...
```

```html
<div class="position-absolute top-0 start-0 w-100 h-100"  style="display:none; background: rgba(0, 0, 0, 0.5); z-index: 100;"  id = "{{  $genericId  }}_loading_indicator">
	<div class="d-flex h-100">
		<div class="m-auto d-flex flex-column">
			<div class="spinner-border mx-auto text-warning"  role="status"></div>
			<div class="text-white">Loading</div>
		</div>
	</div>
</div>
```

## IColumn and IColumnRenderer

### IColumn
The `IColumn` interface is useful when you need to create a custom column. Let's say you want to create your own custom column called IconColumn where depending on any value of the row, the cell of the column will display a icon or another. To achieve that, you need to understand this interface. `IColumn` declares as follows:

```php
interface  IColumn
{
	public  string  $columnTitle {get; set;}
	public ?string  $databaseColumnName {get; set;}
	public  int  $settings {get; set;}
	public ?MappedRoute  $mappedRoute {get; set;}
}
```
1. `public  string  $columnTitle` is the string used by the engine to render the HTML column header output.
2. `public ?string  $databaseColumnName {get; set;}` is the string used to find the database column. As you can see, it can be `null`, as it allows to locate the database column name by other means if this field is `null`. By default the class `Column` implements a use-case behavior when this property is `null` taking `columnTitle` and applying `Str::snake()` to it. This way if users leaves the `databaseColumnName` as null, system will take `columnTitle` in snake case as Laravel does. If you want to implement your own column you will need to implement a similar behavior. It is not a mandatory rule but your column's users will appreciate it.
3. `public  int  $settings {get; set;}` Applys settings flags to the column. Use the `ColumnSettingFlags` enum to do it.
4. `public ?MappedRoute  $mappedRoute {get; set;}` use it only when your column needs to handle links, if not, leave it as `null`

That is all you need to implement, to create your own column definition.  

### IColumnRenderer
Now, the `Column` class implements another interface called `IColumnRenderer` that allows you to define your own rules for the cell rendering. The system has its own, but you can overwrite the method and create your own, its very easy...

```php
interface  IColumnRenderer
{
	public  function  renderCell(Model  $rowModel) : string;
}
```
All you need is implements the above method. Once the system reaches the moment to render your column, it will call your method definition instead of the internal one. Keep in mind that the output will always be rendered as HTML so be aware of the **security concerns**.

An example of how to implement your own column definition
```php
<?php
namespace  App\Tables\Extensions;

use  Closure;
use Mmt\GenericTable\Attributes\MappedRoute;
use Mmt\GenericTable\Interfaces\IColumn;
use Mmt\GenericTable\Interfaces\IColumnRenderer;
use  Str;

class  IconColumn  implements  IColumn, IColumnRenderer
{
	public  int  $settings = 0;
	public ?MappedRoute  $mappedRoute = null;
	public ?string  $databaseColumnName = 'status';
	public  string  $columnTitle = 'Status';
	private  Closure  $setIconCallback;
	
	public  function  __construct()
	{
		if($this->databaseColumnName == null)
			$this->databaseColumnName = Str::snake($this->columnTitle);
	}
	
	public  function  renderCell(\Illuminate\Database\Eloquent\Model  $rowModel): string
	{
		$icon = 'bi bi-arrow-up-right-circle-fill';
		
		if(isset($this->setIconCallback))
			$icon = $this->setIconCallback->call($this, $rowModel);
	
		return  <<<HTML
					<div  class = "w-100 d-flex justify-content-start">
					<i  class = "$icon me-2"></i>
					</div>
				HTML;
	}
	
	public  function  route(MappedRoute  $route)
	{
		$this->mappedRoute = $route;
		return  $this;
	}
	
	public  function  setIconIf(Closure  $callback)
	{
		$this->setIconCallback = $callback;
		return  $this;
	}
}
```
A possible use may be:
```php
$this->columns->add(new  IconColumn()->setIconIf(function(Model  $rowModel) use($icons) {
	if($rowModel->status == 'discontinued') {
		return  $icons['bag-check'] .  ' text-success';
	}
	else {
		return  $icons['bag-x'] .  ' text-danger';
	}
}));
```
## Attributes
The package have some useful attributes that worths mentioning:

1. [CellFormatter](#cellformatter)
2. [OnReorder](#onreorder)


## CellFormatter
When a cell is about to be rendered, the engine will call the method targeted by the attribute, to aply a format to the cell. The attribute expects the `database column name`. The only argument passed to the callback is an instance of the model in representing the entire row. The output will always be treated as HTML string, so **be aware of the security concerns**.

```php
#[CellFormatter('id')]
public function idFormatter(Model $modelItem)
{
    return '<b class = "text-primary">#</b> '.$modelItem->id;
}
```

## OnReorder
This attribute will help you to make a custom handler for the `IDragDropReordering` interface implementation. See [How to Implement IDragDropReordering interface](#idragdropreordering )

## Traits
1. [WithGenericTable](#withgenerictable)
    - [refreshGenericTable](#withgenerictablerefreshgenerictable)
    - [injectParams](#withgenerictableinjectparamsarray-params)
2. [WithColumnBuilder](#withcolumnbuilder)
 

### WithGenericTable
It was designed to be a sort of helper for the `Generic Table Definition`.

#### WithGenericTable::refreshGenericTable()
Dispatch an event called `refreshGenericTable` to force the the component reload

#### WithGenericTable::injectParams(array $params)
Dispatch an event called `injectParams` to "inject" arbitrary data into the `generic table` component. See [How to implement IEvent][idragdropreordering]

### WithColumnBuilder
The `WithColumnBuilder` attribute was designed to give you and your column users a more intuitive way to interact with all the possible combinations of settings in a column. It's recommended that you use it on your custom columns, as it provides some default behaviors. By doing so, you'll comply with the package standards, and users will be able to intuitively find those methods and build tables with clean code.
#### Methods of WithColumnBuilder
 - `hide()` Hides the column 
 - `sortable()` Allows the column to be used in the `orderBy` Eloquent method
 - `defaultSort()` Sets the column as the initial column to be sorted 
 - `defaultSortAsc()` Sets the column as the initial column to be sorted by `ASC`
 - `defaultSortDesc()` Sets the column as the initial column to be sorted by `DESC`
 - `empty()` Tells the system that all the cells of this column will be empty (See [The empty column](#theemptycolumn))
 - `exportable()` This column will present in the export callback
 - `searchable()` Users will be able to search in this column
 - `hookFormatter(Closure)`Intercepts the call of `IColumnRenderer::renderCell(Model  $rowModel)` to use a custom output
