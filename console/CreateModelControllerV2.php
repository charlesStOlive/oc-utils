<?php namespace Waka\Utils\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Winter\Storm\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Twig;


class CreateModelControllerV2 extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:mc2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new model and controller.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */

    protected $controllerPhpStubs = [

        'controller/config_form.stub' => 'controllers/{{lower_ctname}}/config_form.yaml',
        'controller/config_list.stub' => 'controllers/{{lower_ctname}}/config_list.yaml',
        'controller/config_btns.stub' => 'controllers/{{studly_ctname}}/config_btns.yaml',
        'controller/controller.stub' => 'controllers/{{studly_ctname}}.php',

    ];
    protected $controllerHtmStubs = [
        'controller/_list_toolbar.stub' => 'controllers/{{lower_ctname}}/_list_toolbar.htm',
        'controller/create.stub' => 'controllers/{{lower_ctname}}/create.htm',
        'controller/index.stub' => 'controllers/{{lower_ctname}}/index.htm',
        'controller/preview.stub' => 'controllers/{{lower_ctname}}/preview.htm',
        'controller/update.stub' => 'controllers/{{lower_ctname}}/update.htm',
        'controller/sidebar_info.stub' => 'controllers/{{lower_ctname}}/_sidebar_info.htm',

    ];
    protected $modelYamlstubs = [
        'model/fields.stub' => 'models/{{lower_name}}/fields.yaml',
        'model/columns.stub' => 'models/{{lower_name}}/columns.yaml',
    ];

    protected $stubs = [];

    public $pluginObj = [];
    public $relations;

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $this->vars = $this->processVars($this->prepareVars());

        if ($this->maker['model']) {
            /**/trace_log('on fait le modele');
            $this->stubs['model/model.stub'] = 'models/{{studly_name}}.php';
        }
        if ($this->maker['update']) {
            /**/trace_log('on fait le migrateur du modele');
            $this->stubs['model/create_table.stub'] = 'updates/create_{{snake_plural_name}}_table.php';
            //trace_log($this->version);
            if ($this->version) {
                $this->stubs['model/create_update.stub'] = 'updates/create_{{snake_plural_name}}_table_u{{ version }}.php';
            }
        }
        if ($this->maker['lang_field_attributes'] || $this->maker['only_langue']) {
            $this->stubs['model/temp_lang.stub'] = 'lang/fr/{{lower_name}}.php';
        }
        if ($this->maker['lang_field_attributes'] || $this->maker['only_attribute']) {
            if (!$this->config['no_attributes_file'] ?? false) {
                $this->stubs['model/attributes.stub'] = 'models/{{lower_name}}/attributes.yaml';
            }
        }
        if ($this->maker['lang_field_attributes']) {
            /**/trace_log('on fait les langues fields et attributs');
            if ($this->fields_create) {
                $this->stubs['model/fields_create.stub'] = 'models/{{lower_name}}/fields_create.yaml';
            }
            $this->stubs = array_merge($this->stubs, $this->modelYamlstubs);

            if ($this->config['use_tab']) {
                unset($this->stubs['model/fields.stub']);
                $this->stubs['model/fields_tab.stub'] = 'models/{{lower_name}}/fields.yaml';
            }
            // if ($this->config['belong'] && $this->yaml_for) {
            //     foreach ($this->config['belong'] as $relation) {
            //         $this->makeOneStub('model/fields.stub', 'models/' . strtolower($this->w_model) . '/fields_for_' . $relation['relation_name'] . '.yaml', $this->vars);
            //         $this->makeOneStub('model/columns.stub', 'models/' . strtolower($this->w_model) . '/columns_for_' . $relation['relation_name'] . '.yaml', $this->vars);
            //     }
            // }
        }

        if ($this->maker['controller']) {
            /**/trace_log('on fait le controlleur et les configs');
            $this->stubs = array_merge($this->stubs, $this->controllerPhpStubs);
            if ($this->config['behav_duplicate'] ?? false) {
                $this->stubs['controller/config_duplicate.stub'] = 'controllers/{{lower_ctname}}/config_duplicate.yaml';
            }
            if ($this->config['side_bar_attributes'] ?? false) {
                $this->stubs['controller/config_attributes.stub'] = 'controllers/{{lower_ctname}}/config_attributes.yaml';
            }
            if ($this->config['side_bar_info'] ?? false) {
                $this->stubs['controller/config_sidebar_info.stub'] = 'controllers/{{lower_ctname}}/config_sidebar_info.yaml';
            } else {
                unset($this->stubs['controller/sidebar_info.stub']);
            }
            if ($this->config['behav_lots'] ?? false) {
                $this->stubs['controller/config_lots.stub'] = 'controllers/{{lower_ctname}}/config_lots.yaml';
            }
            if ($this->config['behav_workflow'] ?? false) {
                $this->stubs['controller/config_workflow.stub'] = 'controllers/{{lower_ctname}}/config_workflow.yaml';
            }
            if ($this->config['filters'] ?? false) {
                //$this->stubs['controller/config_filter.stub'] = 'controllers/{{lower_ctname}}/' . $this->config['filters'] . '.yaml';
                $this->makeOneStubFromFile('controller/config_filter.stub', 'controllers/{{lower_ctname}}/' . $this->config['filters'] . '.yaml', $this->vars, 'controllers/{{lower_ctname}}/' . $this->config['filters'] . '.yaml' );
            }


            

            if($this->relations->isBehaviorRelationNeeded()) {
                $this->stubs['controller/config_relation.stub'] = 'controllers/{{lower_ctname}}/config_relation.yaml';
            }
            $controllerRelations = $this->relations->getControllerRelations();
            foreach($controllerRelations as $relation) {
                $this->makeOneStub('controller/_field_relation.stub', 'controllers/' . strtolower($this->w_model) . 's/_field_'.$relation['name'].'.htm', $relation);
                if($relation['yamls_read'] ?? false) {
                    $this->makeOneStub('model/fields_for.stub', 'models/' . $relation['singular_name'] . '/fields_for_' . strtolower($this->w_model) . '_read.yaml', []);
                }
                if($relation['yamls_read'] ?? false) {
                    $this->makeOneStub('model/fields_for.stub', 'models/' . $relation['singular_name'] . '/fields_for_' . strtolower($this->w_model) . '_read.yaml', []);
                }
                if($relation['createYamls'] ?? false) {
                    $this->makeOneStubFromFile('model/fields_for.stub', 'models/' . $relation['singular_name'] . '/fields_for_' . strtolower($this->w_model) . '.yaml', [], 'models/' . $relation['singular_name'] . '/fields.yaml' );
                    $this->makeOneStubFromFile('model/columns_for.stub', 'models/' . $relation['singular_name'] . '/columns_for_' . strtolower($this->w_model) . '.yaml', [], 'models/' . $relation['singular_name'] . '/columns.yaml' );
                }
                
            }
        }
        if ($this->maker['html_file_controller']) {
            $this->stubs = array_merge($this->stubs, $this->controllerHtmStubs);
            if ($this->config['side_bar_attributes'] || $this->config['side_bar_info']) {
                unset($this->stubs['controller/update.stub']);
                $this->stubs['controller/update_sidebar.stub'] = 'controllers/{{lower_ctname}}/update.htm';
            }
            if ($this->config['behav_reorder']) {
                $this->stubs['controller/reorder.stub'] = 'controllers/{{lower_ctname}}/reorder.htm';
                $this->stubs['controller/config_reorder.stub'] = 'controllers/{{lower_ctname}}/config_reorder.yaml';
            }
        }

        if ($this->maker['excel']) {
            $this->stubs['imports/import.stub'] = 'classes/imports/{{studly_ctname}}Import.php';
        }

        $this->makeStubs();

        $this->info($this->type . 'created successfully.');
    }

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {
        //trace_log("start");
        $pluginCode = $this->argument('plugin');

        $parts = explode('.', $pluginCode);
        $this->w_plugin = array_pop($parts);
        $this->w_author = array_pop($parts);

        $this->w_model = $this->argument('model');

        // $values = $this->ask('Coller des valeurs excels ', true);
        // trace_log($values);

        $fileName = 'start';

        if ($this->argument('src')) {
            $fileName = $this->argument('src');
        }
        $startPath = null;
        //trace_log($this->w_author);
        if($this->w_author == 'waka') {
            $startPath = env('SRC_WAKA');
        } 
        if($this->w_author == 'wcli') {
            //trace_log(env('SRC_WCLI','merde'));
            $startPath = env('SRC_WCLI');
        }

        $filePath =  $startPath.'/'.$fileName.'.xlsx';

        $this->maker = [
            'model' => true,
            'lang_field_attributes' => true,
            'only_langue' => false,
            'only_attribute' => false,
            'update' => true,
            'controller' => true,
            'html_file_controller' => true,
            'excel' => true,

        ];
        $this->version = null;
        $this->yaml_for = true;

        if ($this->option('option')) {
            $this->maker = [
                'model' => false,
                'lang_field_attributes' => false,
                'only_langue' => false,
                'only_attribute' => false,
                'update' => false,
                'controller' => false,
                'html_file_controller' => false,
                'excel' => false,

            ];
            $types = $this->choice('Database type', ['model', 'lang_field_attributes', 'only_langue', 'only_attribute', 'update', 'controller', 'html_file_controller', 'excel'], 0, null, true);
            //trace_log($types);
            foreach ($types as $type) {
                $this->maker[$type] = true;
                if ($type == 'update') {
                    $this->version = $this->ask('version');
                }
                if ($type == 'lang_field_attributes') {
                    $this->yaml_for = $this->ask('yaml_for');
                }
                if ($type == 'controller') {
                    $this->yaml_for = $this->ask('yaml_for');
                }
            }
        }

        //trace_log($this->maker);

        $importExcel = new \Waka\Utils\Classes\Imports\ImportModelController($this->w_model);
        \Excel::import($importExcel, $filePath);
        $rows = new Collection($importExcel->data->data);
        $this->config = $importExcel->config->data;
        $relations = $importExcel->relations->data;

        // $relationName = null;
        // $pluginRelationName = null;

        $rows = $rows->map(function ($item, $key) {
            $trigger = $item['trigger'] ?? null;
            if ($trigger) {
                if (starts_with($trigger, '!')) {
                    $item['trigger'] = [
                        'field' => str_replace('!', "", $trigger),
                        'action' => 'hide',
                    ];
                } else {
                    $item['trigger'] = [
                        'field' => $trigger,
                        'action' => 'show',
                    ];
                }
            }
            $options = $item['field_options'] ?? null;
            if ($options) {
                $array = explode(',', $options);
                $item['field_options'] = $array;
            }

            $model_opt = $item['model_opt'] ?? null;
            if ($model_opt) {
                $arrayOpt = explode(',', $model_opt);
                $item['append'] = in_array('append', $arrayOpt);
                $item['json'] = in_array('json', $arrayOpt);
                $item['getter'] = in_array('getter', $arrayOpt);
                $item['purgeable'] = in_array('purgeable', $arrayOpt);
            }
            $options = $item['c_field_opt'] ?? null;
            if ($options) {
                $array = explode(',', $options);
                $item['c_field_opt'] = $array;
            }

            

            $field_type = $item['field_type'] ?? null;

            return $item;
        });

        $this->relations = new \Waka\Utils\Classes\CreateRelations($this,$relations);
        $modelRelations = $this->relations->getModelRelations();
        $controllerRelations = $this->relations->getControllerRelations();
        $isBehaviorRelationNeeded = $this->relations->isBehaviorRelationNeeded();

        //Trsnfomation des listes uniques. 
        $this->config['lists'] = $rows->where('lists', '!=', null)->unique('lists')->toArray();
        //
        $trads = $rows->where('name', '<>', null)->toArray();
        //
        $dbs = $rows->where('type', '<>', null)->where('version', '==', null)->toArray();
        $dbVersion = $rows->where('type', '<>', null)->where('version', '==', $this->version)->toArray();
        //trace_log($dbs);

        $columns = $rows->where('column', '<>', null)->sortBy('column')->toArray();
        $fields = $rows->where('field', '<>', null)->sortBy('field')->toArray();
        $this->fields_create = $rows->where('c_field', '<>', null);
        if ($this->fields_create) {
            $this->fields_create = $this->fields_create->sortBy('c_field');
            $this->fields_create = $this->fields_create->map(function ($item, $key) {
                $item['field_options'] = $item['c_field_opt'];
                return $item;
            });
            $this->fields_create = $this->fields_create->toArray();
        }
        //trace_log($this->fields_create);
        //trace_log($fields);
        $attributes = $rows->where('attribute', '<>', null)->toArray();

        //Recherche des tables dans la config
        $tabs = [];
        foreach ($this->config as $key => $value) {
            if (starts_with($key, 'tab::')) {
                $key = str_replace('tab::', "", $key);
                $tabs[$key] = $value;
            }
        }

        //Construction d'un array errors à partir de config, il sera utiliser dans le fichier de lang du modele
        $errors = [];
        foreach ($this->config as $key => $value) {
            if (starts_with($key, 'e.')) {
                $key = str_replace('e.', "", $key);
                $errors[$key] = $value;
            }
        }

        $excels = $rows->where('excel', '<>', null)->toArray();

        $titles = $rows->where('title', '<>', null)->pluck('name', 'var')->toArray();
        $appends = $rows->where('append', '<>', null)->pluck('name', 'var')->toArray();
        $dates1 = $rows->where('type', '==', 'date');
        $dates2 = $rows->where('type', '==', 'timestamp');
        $dates = $dates1->merge($dates2)->pluck('name', 'var')->toArray();
        $requireds = $rows->where('required', '<>', null)->pluck('required', 'var')->toArray();
        $jsons = $rows->where('json', '<>', null)->pluck('json', 'var')->toArray();
        $getters = $rows->where('getter', '<>', null)->pluck('json', 'var')->toArray();
        $purgeables = $rows->where('purgeable', '<>', null)->pluck('purgeable', 'var')->toArray();

        //trace_log($errors);

        $all = [
            'name' => $this->w_model,
            'ctname' => $this->w_model . 's',
            'author' => $this->w_author,
            'plugin' => $this->w_plugin,
            'configs' => $this->config,
            'trads' => $trads,
            'dbs' => $dbs,
            'dbVersion' => $dbVersion,
            'version' => $this->version,
            'columns' => $columns,
            'fields' => $fields,
            'fields_create' => $this->fields_create,
            'attributes' => $attributes,
            'titles' => $titles,
            'appends' => $appends,
            'dates' => $dates,
            'requireds' => $requireds,
            'jsons' => $jsons,
            'getters' => $getters,
            'purgeables' => $purgeables,
            'excels' => $excels,
            'tabs' => $tabs,
            'errors' => $errors,
            'modelRelations' => $modelRelations,
            'controllerRelations' => $controllerRelations,
            'isBehaviorRelationNeeded' => $isBehaviorRelationNeeded,

        ];
        //trace_log($this->config);
        //trace_log($all);

        return $all;
    }

    

    

    protected function processVars($vars)
    {

        $cases = ['upper', 'lower', 'snake', 'studly', 'camel', 'title'];
        $modifiers = ['plural', 'singular', 'title'];

        foreach ($vars as $key => $var) {
            if (!is_array($var) && $var) {
                /*
                 * Apply cases, and cases with modifiers
                 */
                foreach ($cases as $case) {
                    $primaryKey = $case . '_' . $key;
                    $vars[$primaryKey] = $this->modifyString($case, $var);

                    foreach ($modifiers as $modifier) {
                        $secondaryKey = $case . '_' . $modifier . '_' . $key;
                        $vars[$secondaryKey] = $this->modifyString([$modifier, $case], $var);
                    }
                }

                /*
                 * Apply modifiers
                 */
                foreach ($modifiers as $modifier) {
                    $primaryKey = $modifier . '_' . $key;
                    $vars[$primaryKey] = $this->modifyString($modifier, $var);
                }
            } else {
                $vars[$key] = $var;
            }
        }

        return $vars;
    }

    /**
     * Make a single stub.
     *
     * @param string $stubName The source filename for the stub.
     */
    public function makeOneStub($stubName, $destinationName, $tempVar)
    {

        $sourceFile = $this->getSourcePath() . '/' . $stubName;
        $destinationFile = $this->getDestinationPath() . '/' . $destinationName;
        $destinationContent = $this->files->get($sourceFile);

        /*
         * Parse each variable in to the destination content and path
         */
        $destinationContent = Twig::parse($destinationContent, $tempVar);
        $destinationFile = Twig::parse($destinationFile, $tempVar);

        $this->makeDirectory($destinationFile);

        /*
         * Make sure this file does not already exist
         */
        // if ($this->files->exists($destinationFile) && !$this->option('force')) {
        //     throw new \Exception('Stop everything!!! This file already exists: ' . $destinationFile);
        // }

        $this->files->put($destinationFile, $destinationContent);
    }

    public function makeOneStubFromFile($stubName, $destinationName, $tempVar, $srcFileName)
    {

        $sourceFile = $this->getSourcePath() . '/' . $stubName;
        $destinationFile = $this->getDestinationPath() . '/' . $destinationName;
        $srcFile = $this->getDestinationPath() . '/' . $srcFileName;

        $destinationContent = null;
        if ($this->files->exists($srcFile)) {
            $destinationContent = $this->files->get($srcFile);
        } else {
             $destinationContent = $this->files->get($sourceFile);
        }
        //
       

        /*
         * Parse each variable in to the destination content and path
         */
        $destinationContent = Twig::parse($destinationContent, $tempVar);
        $destinationFile = Twig::parse($destinationFile, $tempVar);

        $this->makeDirectory($destinationFile);

        /*
         * Make sure this file does not already exist
         */
        // if ($this->files->exists($destinationFile) && !$this->option('force')) {
        //     throw new \Exception('Stop everything!!! This file already exists: ' . $destinationFile);
        // }

        $this->files->put($destinationFile, $destinationContent);
    }

    // public function getOneFile($stubName) {
    //     $destinationFile = $this->getDestinationPath() . '/' . $this->stubs[$stubName];

    // }

    public function makeStub($stubName)
    {
        if (!isset($this->stubs[$stubName])) {
            return;
        }

        $sourceFile = $this->getSourcePath() . '/' . $stubName;
        $destinationFile = $this->getDestinationPath() . '/' . $this->stubs[$stubName];
        $destinationContent = $this->files->get($sourceFile);

        /*
         * Parse each variable in to the destination content and path
         */
        $destinationContent = Twig::parse($destinationContent, $this->vars);
        $destinationFile = Twig::parse($destinationFile, $this->vars);

        $this->makeDirectory($destinationFile);

        /*
         * Make sure this file does not already exist
         */
        // if ($this->files->exists($destinationFile) && !$this->option('force')) {
        //     throw new Exception('Stop everything!!! This file already exists: ' . $destinationFile);
        // }

        $this->files->put($destinationFile, $destinationContent);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin. Eg: RainLab.Blog'],
            ['model', InputArgument::REQUIRED, 'The name of the model. Eg: Post'],
            ['src', InputArgument::REQUIRED, 'The name of the model. Eg: Post'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
            ['option', null, InputOption::VALUE_NONE, 'Crée uniquement le model'],
        ];
    }
}
