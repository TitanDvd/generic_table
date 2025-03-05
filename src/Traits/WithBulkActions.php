<?php

namespace Mmt\GenericTable\Traits;

use Closure;
use Livewire\WithPagination;
use Mmt\GenericTable\Interfaces\IBulkActions;
use Mmt\GenericTable\Support\BulkAction;
use Mmt\GenericTable\Support\BulkActionCollection;
use Mmt\GenericTable\Support\BulkActionGroup;
use Mmt\GenericTable\Support\BulkActionSettings;
use Str;


/**
 * 
 * @mixin \Mmt\GenericTable\Table
 * 
 */
trait WithBulkActions
{
    use WithPagination;
    
    public array $bulkActionData = [];
    public bool $isAllSelected = false;
    public array $exceptions = [];
    public bool $hasBulkActions;
    
    

    /**
     * @internal
     */
    private function makeWithAllSelection(bool $fill)
    {
        $this->bulkActionData = []; // clear selection from state/memory
        if($fill == true)
            $this->tableItems->each( fn($model) => $this->bulkActionData[$model->id] = true );
    }

    /**
     * @internal
     */
    public function updatedIsAllSelected($value)
    {
        $this->makeWithAllSelection($value);
    }

    /**
     * @internal
     */
    public function updatedPaginators()
    {
        // $this->bulkActionData = []; // Clear previous state
        if($this->isAllSelected) {
            $this->tableItems->each(function($model) {
                
                // If any element of the tableItems match with any of the values
                // in array $exceptions is considered an unselected element
                if(array_find($this->exceptions, fn($e) => $e == $model->id)) {
                    $this->bulkActionData[$model->id] = false;
                }
                else {
                    $this->bulkActionData[$model->id] = true;
                }
            });
        }

        
    }

    /**
     * @internal
     */
    public function bulkActionsDispatcher(string $methodName)
    {
        $selections = [];
        if($this->isAllSelected == true) {
            $this->bulkActionData = [];
        }
        else {
            $selections = array_keys($this->bulkActionData);
            $this->exceptions = [];
        }
        
        $queryBuilder = $this->execQuery(false, true);

        $dispatcher = $this->findBulkActionDispatcher($methodName, iterator_to_array($this->tableObject->bulkActionCollection));

        
        if(isset($dispatcher))
            $dispatcher->call(
                $this->tableObject,
                new BulkActionSettings(
                    $selections,
                    $this->isAllSelected,
                    $this->exceptions,
                    $queryBuilder,
                    $this->model->getKeyName(),
                    $this->model
                )
            );
    }


    private function findBulkActionDispatcher(string $methodName, array $moreActions = []) : Closure|null
    {
        foreach ($moreActions as $actor) {
            if($actor instanceof BulkActionGroup) {
                return $this->findBulkActionDispatcher($methodName, $actor->actions);
            }
            else if($actor instanceof BulkAction) {
                if($actor->actionName == $methodName) {
                    return $actor->callback;
                }
            }
        }
        return null;
    }


    /**
     * @internal
     */
    public function updatedBulkActionData($isSelected, $id)
    {
        if($this->isAllSelected == true) {
            if($isSelected == false){
                $this->exceptions[] = $id;
            }
            else {
                $index = array_find_key($this->exceptions, fn($e) => $e == $id);
                unset($this->exceptions[$index]);
                $this->exceptions = array_values($this->exceptions);
            }
        }
    }

    
    private function updateBulkSelection()
    {
        if($this->isAllSelected == true) {
            $this->makeWithAllSelection(true);
        }
    }
    
    
    final protected function buildNestedBulkActionHtml(array $actionCollection = [])
    {
        if(count($actionCollection) == 0) {
            $actionCollection = iterator_to_array($this->tableObject->bulkActionCollection);
        }

        $html = '';
        
        foreach ($actionCollection as $actor) {
            
            if($actor instanceof BulkActionGroup) {
                $html .= '<div class="dropend">';
                $html .=    "<li class=\"dropdown-item dropdown-toggle\" data-bs-toggle=\"dropdown\" data-bs-auto-close=\"outside\"><span role = \"button\">{$actor->actionLabel}</span></li>";
                $html .=    '<ul class="dropdown-menu">';
                $html .=        $this->buildNestedBulkActionHtml($actor->actions);
                $html .=    '</ul>';
                $html .= '</div>';
            }
            else {
                $requireConfirm = $actor->requireConfirmation ? '1' : '0';
                $html .= "<li class=\"dropdown-item\"><div role = \"button\" wire:click=\"\$dispatch('dispatchBulkEvent', {dispatcher:'{$actor->actionName}', requireConfirmation: $requireConfirm})\">{$actor->actionName}</div></li>";
            }
        }
        
        return $html;
    }
    
}