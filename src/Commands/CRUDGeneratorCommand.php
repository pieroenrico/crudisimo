<?php

namespace Pieroenrico\Crudisimo\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class CRUDGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tropa:crud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a Backend Resource Set';


    private $resources = '/resources/generate-backend';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    protected function createModelsFolder($name)
    {
        if (!file_exists(app_path().'/Models')) {
            mkdir(app_path().'/Models', 0755, true);
        }
        if($name)
        {
            if (!file_exists(app_path().'/Models/' . $name)) {
                mkdir(app_path().'/Models/' . $name, 0755, true);
                $this->line(app_path() . '/Models/' . $name . ' folder created');
            }
        }
    }

    protected function createControllersFolder($name)
    {
        if (!file_exists(app_path().'/Http/Controllers/' . $name)) {
            mkdir(app_path().'/Http/Controllers/' . $name, 0755, true);
            $this->line(app_path() . '/Http/Controllers/' . $name . ' folder created');
        }
    }

    protected function createViewsFolder($name)
    {
        if (!file_exists(base_path().'/resources/views/' . strtolower($name))) {
            mkdir(base_path().'/resources/views/' . strtolower($name), 0755, true);
            $this->line(base_path() . '/resources/views/' . strtolower($name) . ' folder created');
        }
        if (!file_exists(base_path().'/resources/views/' . strtolower($name) . '/partials')) {
            mkdir(base_path().'/resources/views/' . strtolower($name) . '/partials', 0755, true);
            $this->line(base_path() . '/resources/views/' . strtolower($name)  . '/partials' .  ' folder created');
        }
    }

    protected function migration($migration_name, $table_name)
    {
        Artisan::call('make:migration', [
            'name' => $migration_name,
            '--create' => $table_name,
        ]);
        $this->line('Created ' . $migration_name . ' migration');
    }

    protected function model($route_name, $models_namespace, $model_name, $model_path, $table_name)
    {
        $modelTemplate = str_replace([
            '{{ModelPath}}',
            '{{NameOfModel}}',
            '{{NameOfTable}}',
        ],[
            $model_path,
            $model_name,
            $table_name,
        ], $this->getStub('Model'));

        file_put_contents(app_path() . '/' . $this->path2path($model_path) . '/' . $model_name . '.php', $modelTemplate);
        $this->line('Created ' . $model_name . ' model');
    }

    protected function resource($resource_path, $resource_name, $section_name, $holder_route, $route_name, $model_path, $model_name)
    {

        $resourceTemplate = str_replace([
            '{{ResourcePath}}',
            '{{NameOfController}}',
            '{{NameOfSection}}',
            '{{HolderRoute}}',
            '{{SectionRoute}}',
            '{{NameOfModel}}',
        ],[
            $resource_path,
            $resource_name,
            $section_name,
            $holder_route,
            $route_name,
            $model_path. "\\" . $model_name,
        ], $this->getStub('Controller'));

        file_put_contents(app_path() . '/' . $this->path2path($resource_path) . '/' . $resource_name . '.php', $resourceTemplate);
        $this->line('Created ' . $resource_name . ' controller');
    }

    protected function views($store_path, $session_path)
    {
        if (!file_exists($store_path)) {
            mkdir($store_path, 0755, true);
        }
        $this->copyView('index', $store_path);
        $this->line('Created index view');
        $this->copyView('form', $store_path);
        $this->line('Created form view');
        $this->copyView('edit', $store_path);
        $this->line('Created edit view');
        $this->copyView('create', $store_path);
        $this->line('Created create view');

        $sessionTemplate = $this->getStub('session');
        file_put_contents($session_path . '/partials/session.blade.php', $sessionTemplate);
        $this->line('Created session view');

        // copy translations
        if(!file_exists(resource_path('lang/en/backend.php')))
        {
            $langTemplate = $this->getStub('translations');
            file_put_contents(resource_path('lang/en/backend.php'), $langTemplate);
        }
    }

    protected function addToGit($migration_name, $model_path, $model_name, $resource_path, $resource_name, $store_path)
    {
        if($this->confirm('Add to git?'))
        {
            system('git add ' . 'database/migrations/*_' . $migration_name . '.php');
            system('git add ' . 'app/' . $this->path2path($model_path) . '/' . $model_name . '.php');
            system('git add ' . 'app/' . $this->path2path($resource_path) . '/' . $resource_name . '.php');
            system('git add ' . $store_path . '/index.blade.php');
            system('git add ' . $store_path . '/create.blade.php');
            system('git add ' . $store_path . '/edit.blade.php');
            system('git add ' . $store_path . '/form.blade.php');
        }
    }

    protected function addRoute($addedndum)
    {
        File::append(base_path('routes/web.php'), "\n".$addedndum);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->info('Backend Generator');
        $route_name = $this->ask('Route Name');
        if($route_name == "")
        {
            $this->error("You must provide a route name");
            return;
        }


        $models_namespace = $this->dashesToCamelCase($this->ask('Model Namespace'));
        $controllers_namespace = $this->dashesToCamelCase($this->ask('Controllers Namespace', $models_namespace));
        $views_namespace = $this->ask('Views Namespace', strtolower($models_namespace));

        $this->createModelsFolder($models_namespace);
        $this->createControllersFolder($controllers_namespace);
        $this->createViewsFolder($views_namespace);

        $table_name = $this->ask('Table Name', str_plural($route_name));
        $migration_name = 'create_'.$table_name.'_table';
        $this->migration($migration_name, $table_name);

        $model_name = $this->ask('Model Name', $this->dashesToCamelCase($route_name));
        $model_path = $this->anticipate('Model Path', ['App\Models\\' . $models_namespace], "App\Models\\" . $models_namespace);
        $this->model($route_name, $models_namespace, $model_name, $model_path, $table_name);

        $resource_name = $this->ask('Resource Name', $model_name . "sController");
        $resource_path = $this->anticipate('Resource Path', ['App\Http\Controllers\\' . $controllers_namespace], "App\Http\Controllers\\" . $controllers_namespace);
        $holder_route = $views_namespace; //$this->ask('Holder Route');
        $section_name = $this->ask('Section Name');
        $this->resource($resource_path, $resource_name, $section_name, $holder_route, $route_name, $model_path, $model_name);

        $views_path = $this->anticipate('Views Path', ['resources/views/' .($views_namespace!=""?$views_namespace.'/':''). $route_name], "resources/views/" .($views_namespace!=""?$views_namespace.'/':''). $route_name);
        $store_path = base_path() . '/' . $views_path;
        $session_path = 'resources/views/' .($views_namespace!=""?$views_namespace.'/':'');
        $this->views($store_path, $session_path);

        $this->addToGit($migration_name, $model_path, $model_name, $resource_path, $resource_name, $store_path);

        $this->addRoute('$this->resource(\''.$route_name.'\', \''.str_replace('App\Http\Controllers\\', '', $resource_path).'\\\\'.$resource_name.'\')->names(\''.$views_namespace.'.'.$route_name.'\');');

        /*----------------------------------------------------------------------------------------*/

        system('clear');
        $this->info('Backend Scaffold Generated!');
        $this->line('1. Edit <fg=yellow>database/migrations/' . $migration_name . '.php</> file');
        $this->line('2. Run <fg=magenta>php artisan:migrate</>');
        $this->line('3. Add Validation Rules to <fg=yellow>app/' . $this->path2path($resource_path) . '/' . $resource_name . '.php</>');
        $this->line('4. Edit index view located at <fg=yellow>'.$views_path.'/index.blade.php</> to add fields (see examples)');
        $this->line('5. Edit form view located at <fg=yellow>'.$views_path.'/form.blade.php</> to add fields (see examples)');
        $this->line('=================================================');
        $this->line('Files Created:');
        $this->line('database/migrations/' . $migration_name . '.php');
        $this->line('app/' . $this->path2path($model_path) . '/' . $model_name . '.php');
        $this->line('app/' . $this->path2path($resource_path) . '/' . $resource_name . '.php');
        $this->line($store_path . '/index.blade.php');
        $this->line($store_path . '/create.blade.php');
        $this->line($store_path . '/edit.blade.php');
        $this->line($store_path . '/form.blade.php');
        $this->info('Enjoy!');

    }

    private function copyView($file, $store_path)
    {
        $contents = $this->getStub($file);
        file_put_contents($store_path . '/' . $file .'.blade.php', $contents, true);
    }

    private function path2path($path)
    {
        $parts = explode("\\", $path);
        array_shift($parts);
        return implode("/", $parts);
    }

    private function tn2qn($name)
    {
        return str_replace("_", "-", $name);
    }

    private function dashesToCamelCase($string, $capitalizeFirstCharacter = true)
    {

        $str = str_replace('-', '', ucwords($string, '-'));

        if (!$capitalizeFirstCharacter) {
            $str = lcfirst($str);
        }

        return $str;
    }

    protected function getStub($type)
    {
        return file_get_contents(__DIR__ ."/stubs/$type.stub");
    }

}
