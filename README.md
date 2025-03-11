# Introduction
Generic table is the way you need to work with tables.
Define only what you need and all that you needs is covered by the interface design pattern.

# Requirements
1. PHP <b>8.4</b>
2. Laravel >= 11.x
3. Livewire >= 3.x
4. Bootstrap 5.x

# How to install
`composer require mmt/generic_table`

# Features
- Drag and drop rows to simplify reordering. (using [Dragula Js][1])
- Classic column reorder.
- Bind the entire column to a laravel route.
- Create action columns. Customize it using blade views
- Filter by mutually exclusive values or inclusives ones.
- Search by any column
- Use relationships in columns to bind the column with a relationship value.
- and more...
 

# Basic Usage
## First, the `Generic Table Definition`
With the following snippet your were creating the table definition that you
will be passing later to the `@generic_table()` blade's directive.
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

## Second, the livewire component
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
Then, in the view use the `@generic_table()` directive as follow:
```html
<div>
    @generic_table($table)
</div>
```

And thats it!

It's clear that the more features you need, the more set ups you need. By default `generic_table` will detect that if no column definition was made then: 
1. All model attrbutes as intended to be public. 
2. The attributes with sufix _id will be omitted from the public sight.
3. All columns are searchables
4. Nothing is exportable
5. Drag and drop ordering is disabled
6. All columns are sorteable

# Interfaces
The interface design pattern is the core of the project.
With the use of several or all the available interfaces you will be able to control
every aspect of the generic table engine. From here I will try to give you a datailed
break down of all interfaces existing at the moment.

- [IGenericTable](#igenerictable)
- [IBulkAction](#ibulkaction)
- [IDateRangeFilter](#idaterangefilter)
- [ISingleSelectionFilter](#isingleselectionfilter)
- [IMultiSelectionFilter](#imultiselectionfilter)
- [IRowsPerPage](#irowsperpage)
- [IPaginationRack](#ipaginationrack)
- [IActionColumn](#iactioncolumn)
- [IDragDropReordering](#idragdropreordering---powered-by-dragula-js)
- [IEvent](#ievent)
- [IExportable](#iexportable)
- [ILoadingIndicator](#iloadingindicator)

## IGenericTable
This is the main interface. Every class table that you want to define must implements
`IGenericTable` interface. This interface has two properties declarations.
1. `Model|string $model`. This property defines the model from which the engine will extract all the data from database, make filters or searches.
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
`Column`'s constructor first argument is a string in representation of the label of the column, the second parameter is the `database column name` but, if there is no second argument, the engine will use the `snake case` of columns label for `database column name`. For example `SubDepartment` will be mapped to `sub_department` case using the `Str::snake()` method. Also you can use defined relationships to render the value of a column in the relationship. For example:
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
Some times you need to bind the entire column to a laravel route dinamically. For that case you can set up the column like:
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
**`MappedRoute`** takes as first argument a defined ***(existing)*** route name. The second argument would be the parameters expected by that route and here an observation. See how the route parameters is configured...
`['product_id' => ':id']` . In order to be possible for the engine determine what value passes to the route, you need to use a **`:binder`**. This tells the system which `database column value` use in the row to make the route work as expected. The third argument is the link label. If you leave the label empty, system will use the value of that cell to render the link label. Tha last argument is `HrefTarget $target` an `enum` to help you set the `target` attribute of `<a>` tag.

If you need to hide a column for some reason, here is how: Use `ColumnSettingsFlag`.
In the following example you will find some other usefull flags for column configuration.
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
Normally, you would prefer handling the bulk actions in a Laravel Job but, if you need to make some processes in the main thread `BulkActionSettings` gives you some handy tools. Once execution gets to the callback you will have access to the query builder, and of course, for performance reasons, in this point, system will avoid querying the database, so is a developer's job but shall fear you are not 'cause obtaining the selected values is very easy. 
1. Use `$bulkActionSettings->getQueryBuilder();` if you wish to obtain only the query builder **with** the registers to be processed in the where clause. That way, when you executes the query at any time, it will give you what you select.
2. If you have a decent (lite) amount of data you can use `$bulkActionSettings->getSelectedModels()`... but keep in mind that this guy is a dragon of resources. This method will give as well all the selected data.
3. When you use `$bulkActionSettings->getSelectedIds();` all that you will be obtaining is the result of executing `$this->getQueryBuilder()->pluck($this->modelPrimaryKey)->toArray();`


## IDateRangeFilter
This interface has the `DateFilterSettings $dateFilterSettings` property declaration. Note that the class `DateFilterSettings`'s first argument is the column that will be use as filterable date range column. The purpose is give to the users a set of common labeled dates ranges from which the user can pick one of them. As you can imagine, each common date range has its own predefined `DateTime` range. See the example below. <br>
`Note: Labels descriptions cannot be modified by any interface at this moment`

```php
// Example

$this->dateFilterSettings = new DateFilterSettings('created_at', 
    CommonDateFilter::LAST_2_MONTHS,
    CommonDateFilter::LAST_3_MONTHS
);
```

In addition you can specify the case `CommonDateFilter::CUSTOM_RANGE` to instruct the engine to allow the user enter a custom date range from the html date input. Or you can use `CommonDateFilter::ALL_RANGES` case to specify that engine must show all options included `CommonDateFilter::CUSTOM_RANGE` case.

## ISingleSelectionFilter
This interface has the `SelectionFilterSettings $singleSelectionFilterSettings` property declaration. The html binded to this filter will force the user to choose only one possible filter value at a time. So, if you want to filter products by its status the way is:
```php
// Example

$this->singleSelectionFilterSettings = new SelectionFilterSettings('status')
    ->add('Out of stock', 'out_of_stock')
    ->add('Discontinued', 'discontinued')
    ->add('Available', 'available');
```
The class `SelectionFilterSettings` receives as the first argument the column to be filtered, then, using the `builder pattern` you can concatenate the possible values to filter, by using the `add` method. The first argument of the `add` method is the label of the filter while the second is the possible value for that particular case.

## IMultiSelectionFilter
This interfaces behaves the same as ISingleSelectionFilter but multiple values can be selected at a time. The selected values are not inclusives, so they are interpreted as `AND` in the query string. To use the interface you need to implement `SelectionFilterSettings $multiSelectionFilterSettings` property. See example below:
```
// Example

$this->$multiSelectionFilterSettings = new SelectionFilterSettings('status')
    ->add('Out of stock', 'out_of_stock')
    ->add('Discontinued', 'discontinued')
    ->add('Available', 'available');
```

## IRowsPerPage
If you need to change the default behavior of the rows shown per page or explicitly set the initial value of the rows per page you should implement the `IRowsPerPage` interface. The property `$rowsPerPage` will set the initial set of rows shown and `$rowsPerPageOptions` property will overwrite the `Rows per page` options in the html output.

```
// Example

public int $rowsPerPage = 10;
public array $rowsPerPageOptions = [10,20,40,60,80];
```

## IPaginationRack
To overwrite the default behavior of the pagination section of the table, you should implement the interface `IPaginationRack` as follows:

```
// Example

public int $paginationRack = 0;

public function __construct()
{
    // Shows the pagination on top and bottom
    PaginationRack::addFlags($this->paginationRack, PaginationRack::TOP, PaginationRack::BOTTOM);
}
```

## IActionColumn
Usually you need to use a column as a container of buttons which controls some of the actions on the row. This is a common case of `action column`. This interfaces allows you to use one column with this purpose.

```
// Example

public int $actionColumnIndex = -1;

public function actionView(Model $item): \Illuminate\View\View
{
    return view("tables_action_views.edit_delete_details", ['productId' => $item->id]);
}
```

The `public int $actionColumnIndex` property defines the position of where the column should be rendered. If the value is set to `-1` the engine will always set the action column as the last rendered column. By implementing the `public function actionView(Model $item): \Illuminate\View\View` you are telling the engine which view use to render each set of control by each cell of the column. The argument of the `actionView` method is an instance of the model and must return a `\Illuminate\View\View` object.

[1]: https://bevacqua.github.io/dragula/
[idragdropreordering]:#idragdropreordering---powered-by-dragula-js
## IDragDropReordering - (powered by [Dragula JS][1])
Generic table engine helps you to order rows manually. When you implements this interface you will be able to grab any rendered row and drop it any where you need (inside the tbody). **For this case, the engine needs an ordered column from 1 to N** where **N** is the last counting row. Lets say you have 5 records in the Product's table. The table needs to have a column `order` where `order` is from 1 to 5. The engine will not set the ordering for you and you need to keep this in mind when you inert new records. The interface declares only `public string $orderingColumn` property. If you implement this interface and leave the property unset, the engine will use column `order` by default and if you dont have an existing column named `order` horrors will be seen. By default this column will force the system to use this column as a default sorted column. With the following code you will been enabling the manual ordering using drag and drop
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
     * If method exists, should return TRUE indicating to the subsystem that
     * this method will handle the reording.
     * If you do not explicitly return boolean, the subsystem will use FALSE as a default returned value
     */
    
    return false;
}
```

If method exists, should return `TRUE` indicating to the subsystem that this method will handle the reording. If you do not explicitly return boolean, the subsystem will use FALSE as a default returned value and will handle the reording ignoring your custom handler. When you move a record from postion **X** to position **Y** the **X** position is called the ***old position*** and ***Y*** position is called the ***new position***, and as you can understand, the `$model` is the moved element.

## IEvent
Use it when you need to catch some of the internal events of the engine and make custom calculations or modify some thing at your convenience. The interface declares `public function dispatchCallback(EventArgs $arguments): void` method.

### Moments where `dispatchCallback` is fired:
1. When database query is about to be build. The `dispatchCallback` receives a child class of `EvntArgs` called `DatabaseEvent` that is thrown only on initial state of the query builder.
2. When `dispatcher` is invoke from inside of `generic table`. Imagine you need to rise a callback event through the generic table for some reason, mainly to maintain organized logic, well, this is the way. You manually make a `wrie:click = "dispatcher( {object} )"` from an `action column control` for example:


```html
// Example of action column
// edit_delete_details.blade.php

<div>
    <a href="" wire:click.prevent = "$dispatch('edit', {productId:{{ $productId }}})">Edit</a>
    
    <!-- Here you can rise the default EventArgs on your table definition side -->
    <a href="" wire:click.prevent = "dispatcher({productId: {{ $productId }}})">Details</a>
    
    <a href="">Delete</a>
</div>
```

That way, you can struct your tables logic around the table class definition (as should be) and not the livewire component. Of course, some times you will need to throw an event directly to your livewire component and a `$dispatch` from livewire is ok.

Other use case for this interface is to be used as a complement of `param injection`. The `param injection` is nothing more than `generic table` listening for a particular event called `injectParams` where you can pass arbitrary data that will be exposed in any `dispatchCallback`

Lets say, that you have defined a nav-tab of options in your livewire component, and each tab must filter the generic table with some arbitrary or defined value. Using this interface in combination with `param injection` you can set the initial state of the query giving the selected tab and fire `injectParams` event in every update of the livewire model containing the tab value. Lets see a quick example:

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

As you can see in the previous table example definition the `dispatchCallback` implementation is prepare to receive `DatabaseEvent`. When that occurs, the selected tab view from the livewire component can be used to handle the behavior of the query. Now, as you can see there is no initial state for `$arguments->injectedArguments['tabView']` yet...

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
```

```html
// ... and view ...

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

The `@generic_table` directive accepts two arguments. The first is the `FQCN` of table definition while the second is an array of possible arguments. All arguments passed to the initialization of `generic table` component will be exposed as argument of the event args in the `dispatchCallback` method implementation. In the example above you can see how by initializing the `generic table` with predefined values and thanks to generic table engine that exposes those values in the `dispatchCallback` we have the hability to set an initial state in the query builder and with the help of `injectParams` event we can update the value of the array key `tabView` to "keep in track" the nav-tab filter

## IExportable
If you need export all the queried registers you can use `IExportable` interface which declares ` public function onExport(ExportEventArgs $args) :  BinaryFileResponse|Response` method. When you implements this interface a `bootstrap warning button with a SVG cloud` shows up in the top-right corner of the table. System does not handle the export for you, but make it much more easier. Once you have the `public function onExport(ExportEventArgs $args) : BinaryFileResponse|Response` implemented you only needs to `return $args->export();`

```php
// Example

public function onExport(ExportEventArgs $args) : BinaryFileResponse|Response
{
    $args->settings->fileName = 'my_products';
    return $args->export();
}
```

The `ExportEventArgs` have some interesting points that is worth to explain. Once the execution gets to the `onExport` method you have some options to handle. By default, the engine will set a time mark in the exported file name like this `date('YmdHis')`. So, `my_products` may ends up  in `my_products_20250310220005`, but if you don't need it you can set `appendTimeMarkToFilename` to `false` like:
```php
public function onExport(ExportEventArgs $args) : BinaryFileResponse|Response
{
    $args->settings->fileName = 'my_products';
    $args->settings->appendTimeMarkToFilename = false;
    return $args->export();
}
```

Also, if you dont use a `filename` system will assign you the model's name in snake case verison. So, in case of model `MyProducts`, file name will be `my_products_<time_mark>.[extension]`.

### Deadlocks
Once the execution gets to `onExport` no query has been made in order to improve performance. If the data set obtained from the query execution is relatively short, you may have no problem with call directly the `$args->export();` method, but when 300K registers enter the scene, things turns heavy for the server and PHP kills the fun... with reason. If you take a look in the `ExportEventArgs $args` the `generic table` offers you the `Eloquent\Builder` for you to handle the results as you needs, but with all filters applied if they in deed are. With this way (custom handling) you are able to create a Laravel Job at this time, save the file and send it by email or using another channel that you have in mind... but! deadlocks can attack again and thats why I will always recommend to do exports using chunks or with limited amount of data.

### The Maatwebsite\Excel package
If you have installed the [Maatwebsite\Excel package](https://docs.laravel-excel.com/3.1/getting-started/) the `generic table` will use a predefinded template to export the data using this library, otherwise the exported file will be a `csv` file.

## ILoadingIndicator
This interface is usefull only when you need to change the behavior of the loading indicator. When you perform some action inside the `generic table` component there is a Livewire hook waiting for the `request`. The loading indicator shows up when request start and hides when `requests` has been responded. In order to give you full control over the view you need to specify a blade view that serves as a loading indicator and engine will toggle `display: block|none` css property using the `id` attribute of the html tag that has to be called `generic_table_loader`.
See the example:

```php
// Example in table definition

public function tableLoadingIndicatorView(): \Illuminate\View\View
{
    return view('custom-loader');
}
// and view ...
```

```html
<div id = "generic_table_loader" class="position-absolute top-0 start-0 w-100 h-100" style="display:none; background: rgba(0, 0, 0, 0.5)">
    <div class="d-flex h-100">
        <div class="m-auto d-flex flex-column">
            <div class="spinner-border mx-auto text-danger" role="status"></div>
            <div class="text-white">Loading</div>
        </div>
    </div>
</div>
```

# Attributes
The package have some useful attributes that worths to mention:

1. [CellFormatter](#cellformatter)
2. [OnReorder](#onreorder)


## CellFormatter
When a cell is about to be rendered, the engine will call the method targeted by the attribute to aply a format to the cell. The attribute expects the `database column name` to match the `formatter` with the desire column. The only argument passed to the callback is an instance of the model in representation of the entire row. The output will always be treated as HTML string, so **be aware of the security concerns**.

```php
#[CellFormatter('id')]
public function idFormatter(Model $modelItem)
{
    return '<b class = "text-primary">#</b> '.$modelItem->id;
}
```

## OnReorder
This attribute will help you to make a custom handler for the `IDragDropReordering` interface implementation. See [How to Implement IDragDropReordering](#idragdropreordering )

# Traits
1. [WithGenericTable](#withgenerictable)
    - [refreshGenericTable](#withgenerictablerefreshgenerictable)
    - [injectParams](#withgenerictableinjectparamsarray-params)
 

## WithGenericTable
Was designed to be a sort of helper for the `Generic Table Definition`.

### WithGenericTable::refreshGenericTable()
Dispatch an event called `refreshGenericTable` to force the reload of the component

### WithGenericTable::injectParams(array $params)
Dispatch an event called `injectParams` to "inject" arbitrary data in the `generic table` component. See [How to implement IEvent][idragdropreordering]
