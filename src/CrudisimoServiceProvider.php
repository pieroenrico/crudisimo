<?php

namespace Pieroenrico\Crudisimo;

use Illuminate\Support\ServiceProvider;
use Pieroenrico\Crudisimo\Commands\CRUDGeneratorCommand;

class CrudisimoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CRUDGeneratorCommand::class,
            ]);
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
