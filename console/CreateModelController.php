<?php namespace Waka\Utils\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Winter\Storm\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Twig;
use Yaml;


class CreateModelController extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:mc';

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
    protected $stubs = [];
    //
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
        //Création du modele
        if ($this->maker['model']) {
            /**/trace_log('on fait le modele');
            $stub = 'model/model.stub';
            $destination = 'models/'. strtolower($this->w_model) . '.php';
            $src = 'models/'. strtolower($this->w_model) . '.php';
            $fnc = 'keepData';
            $this->makeOneStubFromFile($stub, $destination, $this->vars, $src , $fnc );
        }
        //creation du fichier d'update
        if ($this->maker['update']) {
            /**/trace_log('on fait le migrateur du modele');
            $this->stubs['model/create_table.stub'] = 'updates/create_{{snake_plural_name}}_table.php';
            //trace_log($this->version);
            if ($this->version) {
                $this->stubs['model/create_update.stub'] = 'updates/create_{{snake_plural_name}}_table_u{{ version }}.php';
            }
        }
        //création du fichier le langues.
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
            $modelYamlstubs = [
                'model/fields.stub' => 'models/{{lower_name}}/fields.yaml',
                'model/columns.stub' => 'models/{{lower_name}}/columns.yaml',
            ];
            $this->stubs = array_merge($this->stubs, $modelYamlstubs);
            if ($this->fields_create) {
                $this->stubs['model/fields_create.stub'] = 'models/{{lower_name}}/fields_create.yaml';
            }
            if ($this->config['use_tab']) {
                unset($this->stubs['model/fields.stub']);
                $this->stubs['model/fields_tab.stub'] = 'models/{{lower_name}}/fields.yaml';
            }
        }
        if ($this->maker['controller']) {
            /**/trace_log('on fait le controlleur');
            $controllerPhpStubs = [
                'controller/config_form.stub' => 'controllers/{{lower_ctname}}/config_form.yaml',
                'controller/config_list.stub' => 'controllers/{{lower_ctname}}/config_list.yaml',
            ];
            $this->stubs = array_merge($this->stubs, $controllerPhpStubs);
            //
            $stub = 'controller/controller.stub';
            $destination = 'controllers/{{studly_ctname}}.php';
            $src = 'controllers/{{studly_ctname}}.php';
            $fnc = 'keepData';
            $this->makeOneStubFromFile($stub, $destination, $this->vars, $src , $fnc);
        }
        if ($this->maker['controller_config']) {
            /**/trace_log('on fait les configs du controlleur');
            if ($this->config['behav_duplicate'] ?? false) {
                $this->stubs['controller/config_duplicate.stub'] = 'controllers/{{lower_ctname}}/config_duplicate.yaml';
            }
            if ($this->config['side_bar_attributes'] ?? false) {
                $this->stubs['controller/config_attributes.stub'] = 'controllers/{{lower_ctname}}/config_attributes.yaml';
            }
            if ($this->config['side_bar_info'] ?? false) {
                $stub = 'controller/config_sidebar_info.stub';
                $destination = 'controllers/{{lower_ctname}}/config_sidebar_info.yaml';
                $src = 'controllers/{{lower_ctname}}/config_sidebar_info.yaml';
                $this->makeOneStubFromFile($stub, $destination, $this->vars, $src);
            } 
            if ($this->config['behav_workflow'] ?? false) {
                $stub = 'controller/config_workflow.stub';
                $destination =  'controllers/{{lower_ctname}}/config_workflow.yaml';
                $src = 'controllers/{{lower_ctname}}/config_workflow.yaml';
                $this->makeOneStubFromFile($stub, $destination, $this->vars, $src);
            }
            if ($this->config['filters'] ?? false) {
                 /**/trace_log('on fait les filtres');
                $stub = 'controller/config_filter.stub';
                $destination =  'controllers/{{lower_ctname}}/config_filters.yaml';
                $src = 'controllers/{{lower_ctname}}/config_filters.yaml';
                $this->makeOneStubFromFile($stub, $destination, $this->vars, $src);
            }
                /**/trace_log('on fait les filtres');
            $stub = 'controller/config_btns.stub';
            $destination =  'controllers/{{studly_ctname}}/config_btns.yaml';
            $src = 'controllers/{{studly_ctname}}/config_btns.yaml';
            $this->makeOneStubFromFile($stub, $destination, $this->vars, $src);
            if ($this->config['behav_reorder']) {
                $this->stubs['controller/config_reorder.stub'] = 'controllers/{{lower_ctname}}/config_reorder.yaml';
            }
            if($this->relations->isBehaviorRelationNeeded()) {
                $this->stubs['controller/config_relation.stub'] = 'controllers/{{lower_ctname}}/config_relation.yaml';
            }
            $controllerRelations = $this->relations->getControllerRelations();
            foreach($controllerRelations as $relation) {
                $stub = 'controller/_field_relation.stub';
                $destintion = 'controllers/' . strtolower($this->w_model) . 's/_field_'.$relation['name'].'.htm';
                $this->makeOneStubFromFile($stub, $destintion, $relation);
                if($relation['yamls_read'] ?? false) {
                    $stub = 'model/fields_for.stub';
                    $destintion = 'models/' . $relation['singular_name'] . '/fields_for_' . strtolower($this->w_model) . '_read.yaml';
                    $this->makeOneStubFromFile($stub,$destintion , $this->vars, '/fields.yaml');
                }
            }
        }
        if($this->maker['yaml_relation']) {
            $controllerRelations = $this->relations->getControllerRelations();
            foreach($controllerRelations as $relation) {
                if($relation['yamls_read'] ?? false) {
                    $stub = 'model/fields_for.stub';
                    $destintion = 'models/' . $relation['singular_name'] . '/fields_for_' . strtolower($this->w_model) . '_read.yaml';
                    $this->makeOneStubFromFile($stub,$destintion , $this->vars, '/fields.yaml');
                }
                if($relation['createYamls'] ?? false) {
                    $stub = 'model/fields_for.stub';
                    $destination = 'models/' . $relation['singular_name'] . '/fields_for_' . strtolower($this->w_model) . '.yaml';
                    $src = 'models/' . $relation['singular_name'] . '/fields.yaml';
                    $fnc = 'cleanYaml';
                    $this->makeOneStubFromFile($stub, $destination, [], $src , $fnc );
                    //
                    $stub = 'model/columns_for.stub';
                    $destination = 'models/' . $relation['singular_name'] . '/columns_for_' . strtolower($this->w_model) . '.yaml';
                    $src = 'models/' . $relation['singular_name'] . '/columns.yaml';
                    $fnc = 'cleanYaml';
                    $this->makeOneStubFromFile($stub, $destination, [], $src , $fnc );
                }
            }
        }
        if ($this->maker['controller_htm']) {
            $controllerHtmStubs = [
                'controller/_list_toolbar.stub' => 'controllers/{{lower_ctname}}/_list_toolbar.htm',
                'controller/create.stub' => 'controllers/{{lower_ctname}}/create.htm',
                'controller/index.stub' => 'controllers/{{lower_ctname}}/index.htm',
                'controller/preview.stub' => 'controllers/{{lower_ctname}}/preview.htm',
                'controller/update.stub' => 'controllers/{{lower_ctname}}/update.htm',
            ];
            $this->stubs = array_merge($this->stubs,  $controllerHtmStubs);
            if ($this->config['side_bar_attributes'] || $this->config['side_bar_info']) {
                unset($this->stubs['controller/update.stub']);
                $this->stubs['controller/update_sidebar.stub'] = 'controllers/{{lower_ctname}}/update.htm';
            }
            if ($this->config['behav_reorder']) {
                $this->stubs['controller/reorder.stub'] = 'controllers/{{lower_ctname}}/reorder.htm';
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

        $fileName = $this->w_model;

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
            'yaml_relation' => true,
            'only_langue' => false,
            'only_attribute' => false,
            'update' => true,
            'controller' => true,
            'controller_config' => true,
            'controller_htm' => true,
            'excel' => true,

        ];
        $this->version = null;
        $this->keepOldContent = true;
        if($this->option('noKeep')) {
            $this->keepOldContent = false;
        }

        if ($this->option('option')) {
            $this->maker = [
                'model' => false,
                'lang_field_attributes' => false,
                'yaml_relation' => false,
                'only_langue' => false,
                'only_attribute' => false,
                'update' => false,
                'controller' => false,
                'controller_config' => false,
                'controller_htm' => false,
                'excel' => false,

            ];
            $types = $this->choice('Database type', ['model', 'update', 'lang_field_attributes', 'yaml_relation', 'only_langue', 'only_attribute',  'controller', 'controller_config', 'controller_htm', 'excel'], 0, null, true);
            //trace_log($types);
            foreach ($types as $type) {
                $this->maker[$type] = true;
                if ($type == 'update') {
                    $this->version = $this->ask('version');
                }
            }
        }

        //trace_log($this->maker);

        $importExcel = new \Waka\Utils\Classes\Imports\ImportModelController($this->w_model);
        \Excel::import($importExcel, $filePath);
        $rows = new Collection($importExcel->data->data);
        $this->config = $importExcel->config->data;
        $relations = $importExcel->relations->data;

        $this->relations = new \Waka\Utils\Classes\CreateRelations($this,$relations);
        $modelRelations = $this->relations->getModelRelations();
        $controllerRelations = $this->relations->getControllerRelations();
        $isBehaviorRelationNeeded = $this->relations->isBehaviorRelationNeeded();

        //Suppresion des lignes vides ( sans var )
        $rows = $rows->where('var', '<>', null);

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
            $options = $item['field_opt'] ?? null;
            if ($options) {
                $array = explode(',', $options);
                $item['field_opt'] = $array;
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

            $optionsCol = $item['col_opt'] ?? null;
            if ($optionsCol) {
                $array = explode(',', $optionsCol);
                $item['col_opt'] = $array;
            }

            $optionsAtt = $item['att_opt'] ?? null;
            if ($optionsAtt) {
                $array = explode(',', $optionsAtt);
                $item['att_opt'] = $array;
            }
            $rel = $item['relation'] ?? null;
            if ($rel) {
                $item['relation_parsed'] = $this->relations->getOneRelation($rel);
            }
            

            return $item;
        });

        $columns = $rows->where('column', '<>', null)->sortBy('column')->toArray();

         //R2cuperqtion des listes uniques. 
        $this->config['lists'] = $rows->where('lists', '!=', null)->unique('lists')->toArray();
        //
        $trads = $rows->where('name', '<>', null)->toArray();
        //
        $dbs = $rows->where('type', '<>', null)->where('version', '==', null)->toArray();
        $dbVersion = $rows->where('type', '<>', null)->where('version', '==', $this->version)->toArray();

        //
        $fields = $rows->where('field', '<>', null)->sortBy('field')->toArray();
        $this->fields_create = $rows->where('c_field', '<>', null);
        if ($this->fields_create) {
            $this->fields_create = $this->fields_create->sortBy('c_field');
            $this->fields_create = $this->fields_create->map(function ($item, $key) {
                $item['field_opt'] = $item['c_field_opt'];
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
    public function makeOneStubFromFile($stubName, $destinationName, $tempVar, $srcFileName='', $mappingFnc=null)
    {

        $stubFile = $this->getSourcePath() . '/' . Twig::parse($stubName, $tempVar);
        $destinationFile = $this->getDestinationPath() . '/' . Twig::parse($destinationName, $tempVar);
        $srcFile = $this->getDestinationPath() . '/' . Twig::parse($srcFileName, $tempVar);

        trace_log($stubFile.'    '.$srcFile);

        $destinationContent = null;
        if($stubFile) {
          $destinationContent = $this->files->get($stubFile);  
        }
        if($srcFileName != '') {
            if ($this->files->exists($srcFile) && $mappingFnc && $this->keepOldContent) {
                //Appel d'une fonction dynamique de traitement
                $copiedContent = $this->files->get($srcFile);
                $newContent = $destinationContent;
                $newContent = Twig::parse($newContent, $tempVar);
                $destinationContent = $this->$mappingFnc($copiedContent, $newContent);
            } else if($this->files->exists($srcFile) && $this->keepOldContent) {
                $destinationContent = $this->files->get($srcFile);
            }
        }
        
        /*
         * Parse each variable in to the destination content and path
         */
        $destinationContent = Twig::parse($destinationContent, $tempVar);
        $destinationFile = Twig::parse($destinationFile, $tempVar);

        $this->makeDirectory($destinationFile);
        $this->files->put($destinationFile, $destinationContent);
    }

    public function keepData($srcFileContent, $newContent) {
        $finalContent = null;
        $keepedContent = $this->getKeepedContent($srcFileContent);
        if(!$keepedContent) {
            return $newContent;
        } else {
            return $this->replaceKeepedContent($newContent, $keepedContent );

        }
    }

    public function getKeepedContent($stringContent) {
        $re = '/(?<=\/\/startKeep\/)(.*)(?=\/\/endKeep\/)/s';
        $str = $stringContent;
        if (preg_match($re, $str, $matches)) {
            return $matches[1] ?? null;
        } else {
            return null;
        }
    }

    public function replaceKeepedContent($stringContent, $stringKeeped) {
        $re = '/(?<=\/\/startKeep\/)(.*)(?=\/\/endKeep\/)/s';
        $subst = $stringKeeped;
        $str = $stringContent;
        $finalContent = preg_replace($re, $subst, $str, 1);
        return $finalContent;
    }

    public function cleanYaml($srcFileContent) {
        $yamlParsed = Yaml::parse($srcFileContent);
        // trace_log("before");
        // trace_log($yamlParsed);
        if(!$yamlParsed) {
            return null;
        }
        $firstKey = null;
        if($yamlParsed['fields'] ?? false) {
            $firstKey = 'fields';
        }
        if($yamlParsed['columns'] ?? false) {
            $firstKey = 'columns';
        }
        //On regarde le contenu de fields ou columns //Ne fonctionne pas si tab
        $yamlParsedRows = $yamlParsed[$firstKey];
        foreach($yamlParsedRows as $key=>$line) {
            //trace_log($key);
            //trace_log($line);
            //Cas des colonnes
            if($key ==  strtolower($this->w_model).'_r') {
                unset($yamlParsed[$firstKey][$key]);
            }
            //Cas des fields
            if($key ==  strtolower($this->w_model)) {
                unset($yamlParsed[$firstKey][$key]);
            }
        }
        // trace_log("after");
        // trace_log($yamlParsed);
        return Yaml::render($yamlParsed);
    }

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
            ['src', InputArgument::OPTIONAL, 'The name of the model. Eg: Post'],
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
            ['noKeep', null, InputOption::VALUE_NONE, 'keep value from older files'],
            ['option', null, InputOption::VALUE_NONE, 'Options avancés'],
        ];
    }
}
