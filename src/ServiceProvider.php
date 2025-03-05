<?php

namespace Mmt\GenericTable;

use Exception;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use Livewire\Livewire;
use Str;

class ServiceProvider extends SupportServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__."/routes/web.php");
        $this->loadViewsFrom(__DIR__.'/views', 'generic_table');
        Livewire::component('generic_table', Table::class);
        Blade::directive('generic_table', function(string $expression) {
            
            if(empty($expression)) {
                throw new Exception('You need to specify fully qualified class name (FQCN) in order to mount the generic component via blade directive. Example: `@generic_component(FQCN:string, autoLoadAttributes:bool)`');
            }
            
            $slots = explode(',', $expression);
            
            $tableFqn = trim($slots[0]);
            $args = isset($slots[1]) ? trim($slots[1]) : '[]';

            return "<?=Livewire::mount('Mmt\GenericTable\Table', ['table' => $tableFqn, 'args' => $args]);?>";
        });
    }

    public function register()
    {

    }
}