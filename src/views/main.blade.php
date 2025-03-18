<div class="container-fluid px-2">
    
    @include('generic_table::confirm_action_modal')
    
    <link href="{{route('generic_table_css', ['filename' => 'tableDragDrop.css']) }}" rel="stylesheet">
    <div class="row g-3">

        <div class="col-12 border-bottom">
            <div class="row justify-content-between g-3 py-3">
                
                <div class="col-12 col-lg-8">
                    <div class="row g-3">
                        
                        @if ($this->hasBulkActions)
                            <div class="col-12 col-sm-6 col-lg-auto">
                                <div class="dropdown">
                                    <button class="btn bg-info-subtle dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">Bulk Actions</button>
                                    <ul class="dropdown-menu shadow">
                                        {!! $this->buildNestedBulkActionHtml() !!}
                                    </ul>
                                </div>
                            </div>                            
                        @endif

                        @if(count($this->rowsPerPageOptions) > 2)
                            <div class="col-12 col-sm-6 col-lg-auto">
                                <select class="form-select" wire:model.live = "rowsPerPage">
                                    @foreach ($this->rowsPerPageOptions as $rpp)
                                        <option value="{{ $rpp }}">{{ $rpp }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($useDragDropReordering)
                            <div class="col-12 col-sm-6 col-lg-auto">
                                <button id = "generic_ordering_button" class="w-100 btn btn-{{ $isReordering == true ? 'warning' : 'primary' }}" wire:click = "$dispatch('genericTableHandleOrdering')">
                                    @if($isReordering == true)
                                        Finish reorder!
                                    @else
                                        Start ordering
                                    @endif
                                </button>
                            </div>
                        @endif

                        {{-- Draw filters --}}
                        @if($this->filters->count > 0)
                        
                            @foreach ($this->filters as $filter)
                            
                                <div class="col-12 col-sm-6 col-lg-auto">

                                    @if($this->isSingleSelectionFilter($filter))
                                        <select class="form-select" wire:model.live = "appliedSingleSelectionFilters">
                                            <option value="" hidden>Filter by {{ $filter->column }}</option>
                                            @foreach ($filter->possibleValues as $key => $value)
                                                <option value="{{ json_encode(['column' => $filter->column, 'value' => $value, 'label' => $key]) }}">{{ $key }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @if($this->isMultiSelectionFilter($filter))
                                        <div class="dropdown h-100 d-flex w-100  border rounded" role="button">
                                            <div style="padding: 7px" class="w-100 my-auto d-flex justify-content-between" data-bs-toggle="dropdown" data-bs-auto-close="outside" wire:ignore.self>
                                                <div class="my-auto me-2" style="margin-left: 6px">Filter by {{ $filter->column }}</div>

                                                <svg class="me-1 my-auto" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="6 9 12 15 18 9"></polyline>
                                                </svg>
                                            </div>
                                            <ul class="dropdown-menu" wire:ignore.self>
                                                @foreach ($filter->possibleValues as $key => $value)
                                                    <li class="dropdown-item">
                                                        <label>
                                                            <input value = "{{ json_encode(['column' => $filter->column, 'value' => $value, 'label' => $key]) }}" wire:model.live = "appliedMultiSelectionFilters" type="checkbox">
                                                            {{ $key }}
                                                        </label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    @if($this->isDateFilter($filter))
                                        @if($this->isCustomFilterApplied())
                                            <div class="row g-3">
                                                <div class="col-5">
                                                    <input wire:model.live = "customStartDateFilter" type="date" class="form-control">
                                                </div>
                                                <div class="col-5">
                                                    <input wire:model.live = "customEndDateFilter" type="date" class="form-control">
                                                </div>
                                                <div class="col-2">
                                                    <div role="button" class="rounded bg-danger text-white d-flex h-100 w-100" wire:click = "removeCustomDateRangeFilter">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="m-auto" width="24" height="24" viewBox="0 0 24 24">
                                                            <path fill="currentColor" d="M19 19H5V8h14m0-5h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2M9.31 17l2.44-2.44L14.19 17l1.06-1.06l-2.44-2.44l2.44-2.44L14.19 10l-2.44 2.44L9.31 10l-1.06 1.06l2.44 2.44l-2.44 2.44z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <select class="form-select" wire:model.live = "dateRangeAppliedFilters">
                                                <option value="-1" hidden>Select a common date range...</option>
                                                @foreach ($filter->possibleValues as $filterName => $filterValue)
                                                    <option value="{{ json_encode(['column' => $filter->column, 'value' => $filterValue, 'label' => $filterName]) }}">{{ $filterName }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    @endif
                                </div>

                            @endforeach

                        @endif
                        
                    </div>
                </div>

                

                <div class="col-12 col-lg-3">
                    
                    <div class="row g-{{ $useExport == true ? '2' : '0' }}">

                        @if( count(($searchableColumns = $this->resolveSearchColumns())) > 0)
                            <div class="col-{{ $useExport == true ? '10' : '' }}">
                                <div class="input-group">
                                    @if($this->searchKeyWord != '' && count($this->searchByColumns))
                                        <button class="btn btn-warning btn-sm" wire:click = "resetSearch">
                                            <svg 
                                                xmlns="http://www.w3.org/2000/svg" 
                                                width="24" 
                                                height="24" 
                                                viewBox="0 0 24 24">
                                                    <path 
                                                        fill="currentColor" 
                                                        d="M14.76 20.83L17.6 18l-2.84-2.83l1.41-1.41L19 16.57l2.83-2.81l1.41 1.41L20.43 18l2.81 2.83l-1.41 1.41L19 19.4l-2.83 2.84zM12 12v7.88c.04.3-.06.62-.29.83a.996.996 0 0 1-1.41 0L8.29 18.7a.99.99 0 0 1-.29-.83V12h-.03L2.21 4.62a1 1 0 0 1 .17-1.4c.19-.14.4-.22.62-.22h14c.22 0 .43.08.62.22a1 1 0 0 1 .17 1.4L12.03 12z"/>
                                                </svg>
                                        </button>
                                    @endif
        
                                    <input {{ count($this->selectedSearchColumn()) == 0 ? 'disabled' : '' }} wire:model = "searchKeyWord" type="text" class="form-control" placeholder="Search...">
        
                                    <div class="btn-group">
        
                                        <div class="dropdown-menu shadow" wire:ignore.self>
        
                                            <form class="px-3 pt-2 " style="width: 250px">
                                                <li class="p-2 text-center" style="cursor: default">Select columns to search by</li>
                                                <hr class="dropdown-divider" />
                    
                                                <div class="p-2 pb-1">
                                                    @foreach ($searchableColumns as $i => $column)
                                                        <div>
                    
                                                            <label class="mb-3" data-bs-auto-close="outside">
                                                                <input type="checkbox" wire:model.live = "searchByColumns.{{ $i }}.{{ $column->databaseColumnName }}">
                                                                {{ $column->columnTitle }}
                                                            </label>
                                                            
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </form>
                                        </div>
        
                                        <button {{ count($this->selectedSearchColumn()) == 0 ? 'disabled' : '' }} class="btn btn-primary btn-sm" wire:click = "search">Search</button>
                                        <button
                                            class="btn btn-secondary btn-sm" 
                                            data-bs-toggle="dropdown" 
                                            data-bs-auto-close="outside" 
                                            wire:click = "$dispatch('toggleSearchBoxState')" wire:ignore.self>
                                                <svg xmlns="http://www.w3.org/2000/svg" 
                                                    width="20" 
                                                    height="20" 
                                                    viewBox="0 0 20 20">
                                                        <path 
                                                            fill="currentColor" 
                                                            d="M8.5 3a5.5 5.5 0 0 1 4.227 9.02l4.127 4.126a.5.5 0 0 1-.638.765l-.07-.057l-4.126-4.127q-.514.428-1.12.723a5.5 5.5 0 0 0-.286-.977a4.5 4.5 0 1 0-6.562-3.281q-.496.136-.952.358Q3 9.04 3 8.5A5.5 5.5 0 0 1 8.5 3m-5.435 8.442a2 2 0 0 1-1.43 2.478l-.461.118a4.7 4.7 0 0 0 .01 1.016l.35.083a2 2 0 0 1 1.455 2.519l-.126.422q.387.307.834.518l.325-.344a2 2 0 0 1 2.91.002l.337.358q.44-.203.822-.498l-.156-.556a2 2 0 0 1 1.43-2.479l.461-.117a4.7 4.7 0 0 0-.01-1.017l-.349-.082a2 2 0 0 1-1.456-2.52l.126-.421a4.3 4.3 0 0 0-.835-.519l-.324.344a2 2 0 0 1-2.91-.001l-.337-.358a4.3 4.3 0 0 0-.822.497zM5.5 15.5a1 1 0 1 1 0-2a1 1 0 0 1 0 2"/>
                                                </svg>
                                        </button>
                                        
                                    </div>
                                    
                                </div>
                            </div>
                        @endif

                        @if($useExport == true)
                            <div class="col-2">
                                <div role="button" class="d-flex p-1 rounded bg-warning h-100" data-bs-toggle="tooltip" data-bs-title="Export" wire:click = "internalExport">
                                    <svg
                                        class="m-auto"
                                        xmlns="http://www.w3.org/2000/svg" 
                                        width="24" 
                                        height="24" 
                                        viewBox="0 0 24 24" 
                                        fill="none" 
                                        stroke="currentColor" 
                                        stroke-width="2" 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round">
                                            <polyline points="8 17 12 21 16 17"></polyline>
                                            <line x1="12" y1="12" x2="12" y2="21"></line>
                                            <path d="M20.88 18.09A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.29"></path>
                                    </svg>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        {{-- List of applied filters --}}
        @if( count(($appliedFilters = $this->appliedFilters())) > 0)
            <div class="col-12 border-bottom m-0 p-1">
                <div class="row gap-2 ms-2">
                    <small class="col-auto p-0">Applied Filters:</small>

                    @foreach ($appliedFilters as $filter)
                        <div class="col-auto badge bg-primary-subtle text-primary d-flex">
                            <div class="me-1">{{ $filter['column'] }}: {{ $filter['label'] }}</div>
                            <svg
                                wire:click="removeFilter('{{ $filter['type'] }}', '{{ $filter['column'] }}', '{{ $filter['value'] }}')"
                                role="button"
                                xmlns="http://www.w3.org/2000/svg" 
                                width="14" 
                                height="14" 
                                viewBox="0 0 24 24" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2" 
                                stroke-linecap="round" 
                                stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    
        @if( $this->showPaginationRackOnTop() )
            <div class="col-12 generic_pagination_link border-bottom">
                {{ $this->tableItems->links() }}
            </div>
        @endif

        @php
            $columnActionIndex = $this->actionIndex();
            $isAllowSorting = fn($column, $isReordering) => $this->columnIsSorteable($column) == true && $isReordering == false; 
        @endphp

        <div class="col-12 px-0 overflow-auto mb-3 position-relative">
            @if($this->tableItems->count() == 0)
                <div class="mx-3">
                    <div class="alert alert-warning text-center m-0 p-3">
                        There is no data to show
                    </div>
                </div>
            @else
                
                {{-- Shows the loading indicator when a request is sent to the server --}}
                {{ $this->tableLoader() }}

                <table class="table m-0" id ="generic_table">
                    <thead>

                        @if($this->hasBulkActions)
                            <th>
                                <input type="checkbox" wire:model.live = "isAllSelected">
                            </th>
                        @endif

                        @if($useDragDropReordering == true && $isReordering)
                        <th></th>
                        @endif

                        @foreach ($this->tableObject->columns as $index => $column)

                            @continue($this->columnIsHidden($column))

                            @if($columnActionIndex == $index && $isActionColumnActive == true)
                                <th>Action</th>
                            @endif

                            <th {!! $isAllowSorting($column, $isReordering) == true ? "wire:click = \"sortColumn('{$column->databaseColumnName}')\" role = \"button\"" : '' !!}>

                                <div class="d-flex px-0 py-1 justify-content-between">
                                    <div class="my-auto me-2">{{ $column->columnTitle }}</div>
                                    
                                    @if( $isAllowSorting($column, $isReordering) && ($this->sortedColumn == $column->databaseColumnName || $this->sortedColumn == ''))
                                        <div class="d-flex flex-column my-auto">

                                            @if($this->sort == 1 || $this->sort == 0)
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="18 15 12 9 6 15"></polyline>
                                                </svg>
                                            @endif

                                            @if($this->sort == 2 || $this->sort == 0)
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="6 9 12 15 18 9"></polyline>
                                                </svg>
                                            @endif
                                        </div>
                                    @endif

                                </div>
                            </th>
                            
                            @if( $columnActionIndex-1 >= $index && $index == $this->columns->count-1 && $isActionColumnActive == true)
                                <th>Action</th>
                            @endif

                        @endforeach

                        @if( $columnActionIndex == -1 && $isActionColumnActive == true )
                            <th>Action</th>
                        @endif
                    </thead>
            
                    <tbody >
                        @foreach ($this->tableItems as $index => $modelItem)

                            <tr ordering-data="{{ json_encode(['id' => $modelItem->id, 'position' => $index]) }}" wire:key = "{{ $modelItem->id }}">

                                @if($this->hasBulkActions)
                                    <td>
                                        <input type="checkbox"  wire:model.live = "bulkActionData.{{ $modelItem->id }}"/>
                                    </td>
                                @endif

                                @if($useDragDropReordering == true && $isReordering)
                                    <td>
                                        <svg class="generic_handle" style="cursor: grab" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-move">
                                            <polyline class="generic_handle" points="5 9 2 12 5 15"></polyline>
                                            <polyline class="generic_handle" points="9 5 12 2 15 5"></polyline>
                                            <polyline class="generic_handle" points="15 19 12 22 9 19"></polyline>
                                            <polyline class="generic_handle" points="19 9 22 12 19 15"></polyline>
                                            <line class="generic_handle" x1="2" y1="12" x2="22" y2="12"></line>
                                            <line class="generic_handle" x1="12" y1="2" x2="12" y2="22"></line>
                                        </svg>
                                    </td>
                                @endif
                                
                                @foreach ($this->tableObject->columns as $idx => $column)

                                    @continue($this->columnIsHidden($column))
                                    
                                    @if($columnActionIndex == $idx && $isActionColumnActive == true)
                                        <td>{{ $this->actionView($modelItem) }}</td>
                                    @endif
                                    
                                    <td class="text-truncate">
                                        {!! $this->cellHtmlOutput($column, $modelItem) !!}
                                    </td>

                                    @if( $columnActionIndex-1 >= $idx && $idx == $this->columns->count-1 && $isActionColumnActive == true)
                                        <td>{{ $this->actionView($modelItem) }}</td>
                                    @endif

                                @endforeach

                                @if( $columnActionIndex == -1 && $isActionColumnActive == true)
                                    <td>{{ $this->actionView($modelItem) }}</td>
                                @endif
                            </tr>

                        @endforeach
                    </tbody>
            
                </table>

            @endif
        </div>
    
        @if( $this->showPaginationRackOnBottom() )
            <div class="col-12 generic_pagination_link">
                {{ $this->tableItems->links() }}
            </div>
        @endif
    </div>
    
    <script src ="{{ route('generic_table_js', ['filename' => 'dragula.min.js']) }}"  type="text/javascript"></script>
    <script src ="{{ route('generic_table_js', ['filename' => 'tableDragDrop.js']) }}"  type="text/javascript"></script>
    <script>
        /** Global scope of the bulk action method name */
        let bulkActionMethodName = null;

        document.addEventListener('livewire:initialized', () => {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            Livewire.on('orderingCompleted', args => {
                @this.tableReOrderCompleted(args.order, args.data)
            });

            let toggleLoader = () => {
                let loader = document.querySelector('#generic_table_loader');
                if(loader) {
                    if(loader.style.display === 'none') {
                        loader.style.display = 'block';
                    }
                    else {
                        loader.style.display = 'none';
                    }
                }
            }

            /** Dispatchs the event to a confirmation modal */
            Livewire.on('dispatchBulkEvent', ({dispatcher, requireConfirmation})=>{
                

                if(requireConfirmation == 1) {
                    const myModalAlternative = new bootstrap.Modal('#confirmBulkOperation')
                    myModalAlternative.show();
                    bulkActionMethodName = dispatcher;
                }
                else {
                    @this.bulkActionsDispatcher(dispatcher);
                }
            });

            /** Dispatch an event to server */
            Livewire.on('dispatchBulkMethodName', () => {
                @this.bulkActionsDispatcher(bulkActionMethodName);
            });

            Livewire.on('genericTableHandleOrdering', () => {
                @this.genericSystemTableHandleOrdering();
            })

            Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                
                if(component.name == 'generic_table') {
                    toggleLoader();
            
                    respond(() => {
                        toggleLoader();
                    })
                }

            });
        });

        
    </script>
</div>