<?php namespace Waka\Utils\Console;

use October\Rain\Scaffold\GeneratorCommand;
use October\Rain\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Twig;

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

    protected $controllerPhpStubs = [

        'controller/config_form.stub' => 'controllers/{{lower_ctname}}/config_form.yaml',
        'controller/config_list.stub' => 'controllers/{{lower_ctname}}/config_list.yaml',
        'controller/controller.stub' => 'controllers/{{studly_ctname}}.php',

    ];
    protected $controllerHtmStubs = [
        'controller/_list_toolbar.stub' => 'controllers/{{lower_ctname}}/_list_toolbar.htm',
        'controller/create.stub' => 'controllers/{{lower_ctname}}/create.htm',
        'controller/index.stub' => 'controllers/{{lower_ctname}}/index.htm',
        'controller/preview.stub' => 'controllers/{{lower_ctname}}/preview.htm',
        'controller/update.stub' => 'controllers/{{lower_ctname}}/update.htm',

    ];
    protected $modelYamlstubs = [
        'model/fields.stub' => 'models/{{lower_name}}/fields.yaml',
        'model/columns.stub' => 'models/{{lower_name}}/columns.yaml',
    ];

    protected $stubs = [];

    public $pluginObj = [];

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
        if ($this->maker['lang_field_attributes']) {
            /**/trace_log('on fait les langues fields et attributs');
            if (!$this->config['no_attributes_file'] ?? false) {
                $this->stubs['model/attributes.stub'] = 'models/{{lower_name}}/attributes.yaml';
            }
            if ($this->fields_create) {
                $this->stubs['model/fields_create.stub'] = 'models/{{lower_name}}/fields_create.yaml';
            }
            $this->stubs = array_merge($this->stubs, $this->modelYamlstubs);
            $this->stubs['model/temp_lang.stub'] = 'lang/fr/{{lower_name}}.php';
            if ($this->config['use_tab']) {
                unset($this->stubs['model/fields.stub']);
                $this->stubs['model/fields_tab.stub'] = 'models/{{lower_name}}/fields.yaml';
            }
            if ($this->config['belong'] && $this->yaml_for) {
                foreach ($this->config['belong'] as $relation) {
                    $this->makeOneStub('model/fields.stub', 'models/' . strtolower($this->w_model) . '/fields_for_' . $relation['relation_name'] . '.yaml', $this->vars);
                    $this->makeOneStub('model/columns.stub', 'models/' . strtolower($this->w_model) . '/columns_for_' . $relation['relation_name'] . '.yaml', $this->vars);
                }
            }
        }

        if ($this->maker['controller']) {
            /**/trace_log('on fait le controlleur et les configs');
            $this->stubs = array_merge($this->stubs, $this->controllerPhpStubs);
            if ($this->config['behav_duplicate']) {
                $this->stubs['controller/config_duplicate.stub'] = 'controllers/{{lower_ctname}}/config_duplicate.yaml';
            }
            if ($this->config['side_bar_attributes']) {
                $this->stubs['controller/config_attributes.stub'] = 'controllers/{{lower_ctname}}/config_attributes.yaml';
            }
            if ($this->config['side_bar_info']) {
                $this->stubs['controller/config_sidebar_info.stub'] = 'controllers/{{lower_ctname}}/config_sidebar_info.yaml';
            }
            if ($this->config['behav_lots']) {
                $this->stubs['controller/config_lots.stub'] = 'controllers/{{lower_ctname}}/config_lots.yaml';
            }
            //trace_log("--MORPHMANY--");
            //trace_log($this->config['morphmany']);

            if (($this->config['many'] || $this->config['morphmany']) && $this->yaml_for) {

                foreach ($this->config['many'] as $relation) {
                    $this->makeOneStub('controller/_field_relation.stub', 'controllers/' . strtolower($this->w_model) . 's/_field_{{relation_name}}.htm', $relation);
                    if ($relation['createYamlRelation']) {
                        $this->makeOneStub('model/fields_for.stub', 'models/' . $relation['singular_name'] . '/fields_for_' . strtolower($this->w_model) . '.yaml', []);
                        $this->makeOneStub('model/columns_for.stub', 'models/' . $relation['singular_name'] . '/columns_for_' . strtolower($this->w_model) . '.yaml', []);
                    }

                }
                foreach ($this->config['morphmany'] as $relation) {
                    $this->makeOneStub('controller/_field_relation.stub', 'controllers/' . strtolower($this->w_model) . 's/_field_{{relation_name}}.htm', $relation);
                    if ($relation['createYamlRelation']) {
                        $this->makeOneStub('model/fields_for.stub', 'models/' . $relation['singular_name'] . '/fields_for_' . strtolower($this->w_model) . '.yaml', []);
                        $this->makeOneStub('model/columns_for.stub', 'models/' . $relation['singular_name'] . '/columns_for_' . strtolower($this->w_model) . '.yaml', []);
                    }
                }

                $this->stubs['controller/config_relation.stub'] = 'controllers/{{lower_ctname}}/config_relation.yaml';
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
        $pluginCode = $this->argument('plugin');

        $parts = explode('.', $pluginCode);
        $this->w_plugin = array_pop($parts);
        $this->w_author = array_pop($parts);

        $this->w_model = $this->argument('model');

        // $values = $this->ask('Coller des valeurs excels ', true);
        // trace_log($values);

        $fileName = 'start';

        if ($this->option('file')) {
            $fileName = $this->option('file');
        }

        $this->maker = [
            'model' => true,
            'lang_field_attributes' => true,
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
                'update' => false,
                'controller' => false,
                'html_file_controller' => false,
                'excel' => false,
            ];
            $types = $this->choice('Database type', ['model', 'lang_field_attributes', 'update', 'controller', 'html_file_controller', 'excel'], 0, null, true);
            //trace_log($types);
            foreach ($types as $type) {
                $this->maker[$type] = true;
                if ($type == 'update') {
                    $this->version = $this->ask('version');
                }
                if ($type == 'lang_field_attributes') {
                    $this->yaml_for = $this->ask('yaml_for');
                }
            }
        }

        $importExcel = new \Waka\Utils\Classes\Imports\ImportModelController($this->w_model);
        \Excel::import($importExcel, plugins_path('waka/wconfig/updates/files/' . $fileName . '.xlsx'));
        $rows = new Collection($importExcel->data->data);
        $this->config = $importExcel->config->data;

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

            $item = $this->getRelations($item);

            $field_type = $item['field_type'] ?? null;

            return $item;

        });

        $this->config['belong'] = $rows->where('belong', '!=', null)->pluck('belong')->toArray();
        $this->config['many'] = $rows->where('many', '!=', null)->pluck('many')->toArray();
        $this->config['manythrough'] = $rows->where('manythrough', '!=', null)->pluck('manythrough')->toArray();
        $this->config['morphmany'] = $rows->where('morphmany', '!=', null)->pluck('morphmany')->toArray();
        $this->config['morphone'] = $rows->where('morphone', '!=', null)->pluck('morphone')->unique('relation_name')->toArray();
        $this->config['attachOne'] = $rows->where('attachOne', '!=', null)->pluck('attachOne')->toArray();
        $this->config['attachMany'] = $rows->where('attachMany', '!=', null)->pluck('attachMany')->toArray();
        $this->config['lists'] = $rows->where('lists', '!=', null)->pluck('lists')->toArray();

        //trace_log($rows->toArray());
        //trace_log($this->config);

        $trads = $rows->where('name', '<>', null)->toArray();

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

        $tabs = [];
        foreach ($this->config as $key => $value) {
            if (starts_with($key, 'tab::')) {
                $key = str_replace('tab::', "", $key);
                $tabs[$key] = $value;
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

        ];
        //trace_log($this->config);
        //trace_log($all);

        return $all;
    }

    public function getRelations($item)
    {
        $relation = $item['relation'] ?? null;
        if (!$relation) {
            return $item;
        }
        $array = explode('::', $relation);
        $type = $array[0];
        $relationClass = $this->getRelationClass($array[1], $item['var']);
        $createYamlRelation = $this->createYamlRelation($array[1], $item['var']);
        $relationPath = $this->getRelationPath($array[1], $item['var'], $createYamlRelation);
        $options = $this->getRelationOptions($array[2] ?? null);
        $userRelation = $relationClass == 'Backend\Models\User' ? true : false;
        if ($type == 'belong') {
            $item['belong'] = [
                'relation_name' => $item['var'],
                'relation_class' => $relationClass,
                'relation_path' => $relationPath,
                'options' => $options,
                'userRelation' => $userRelation,
                'createYamlRelation' => $createYamlRelation,
            ];
        }
        if ($type == 'morphmany') {
            $item['morphmany'] = [
                'relation_name' => $item['var'],
                'singular_name' => str_singular($item['var']),
                'relation_path' => $relationPath,
                'relation_class' => $relationClass,
                'options' => $options,
                'createYamlRelation' => $createYamlRelation,
            ];
        }
        if ($type == 'morphone') {
            $item['morphone'] = [
                'relation_name' => $this->getRelationKeyVar($array[1], $item['var']),
                'relation_class' => $relationClass,
                'relation_path' => $relationPath,
                'options' => $options,
                'userRelation' => $userRelation,
                'createYamlRelation' => $createYamlRelation,
            ];
        }
        if ($type == 'many') {
            $item['many'] = [
                'relation_name' => $item['var'],
                'singular_name' => str_singular($item['var']),
                'relation_path' => $relationPath,
                'relation_class' => $relationClass,
                'options' => $options,
                'createYamlRelation' => $createYamlRelation,
            ];
        }
        if ($type == 'manythrough') {
            $item['manythrough'] = [
                'relation_name' => $item['var'],
                'singular_name' => str_singular($item['var']),
                'relation_path' => $relationPath,
                'relation_class' => $relationClass,
                'options' => $options,
                'createYamlRelation' => $createYamlRelation,
            ];
        }
        if ($type == 'attachMany') {
            $item['attachMany'] = [
                'relation_name' => $item['var'],
                'relation_class' => $relationClass,
            ];
        }
        if ($type == 'attachOne') {
            $item['attachOne'] = [
                'relation_name' => $item['var'],
                'relation_class' => $relationClass,
            ];
        }
        return $item;
    }

    public function getRelationKeyVar($value, $key)
    {
        $parts = explode('.', $value);
        $r_author = $parts[0];
        $r_plugin = $parts[1];
        $r_model = $parts[2] ?? camel_case(str_singular($key));
        return $r_model;
    }

    public function createYamlRelation($value, $key)
    {
        $returnVar = true;
        $noYaml = $this->config['no_yaml_for'] ?? "";
        $yamlInModel = $this->config['yaml_in_model'] ?? "";
        $noYaml = explode(",", $noYaml);
        $yamlInModel = explode(",", $yamlInModel);
        //trace_log($key);
        if (in_array($key, $noYaml)) {
            $returnVar = false;
        }
        if (in_array($key, $yamlInModel)) {
            $returnVar = 'inModel';
        }
        return $returnVar;
    }

    public function getRelationClass($value, $key)
    {
        if ($value == 'self') {
            return ucfirst($this->w_author) . '\\' . ucfirst($this->w_plugin) . '\\Models\\' . ucfirst(camel_case(str_singular($key)));
        } elseif ($value == 'user') {
            return 'Backend\Models\User';
        } elseif ($value == 'cloudi') {
            return 'Waka\Cloudis\Models\CloudiFile';
        } elseif ($value == 'file') {
            return 'System\Models\File';
        } else {
            $parts = explode('.', $value);
            $r_author = $parts[0];
            $r_plugin = $parts[1];
            $r_model = $parts[2] ?? camel_case(str_singular($key));
            return ucfirst($r_author) . '\\' . ucfirst($r_plugin) . '\\Models\\' . ucfirst($r_model);
        }
    }

    public function getRelationPath($value, $key, $createYamlRelation)
    {
        if ($value == 'self') {
            return '$/' . strtolower($this->w_author) . '/' . strtolower($this->w_plugin) . '/models/' . strtolower(str_singular($key));
        } elseif ($value == 'user') {
            return '$/' . strtolower($this->w_author) . '/' . strtolower($this->w_plugin) . '/models/' . strtolower(str_singular($key));
        } elseif ($createYamlRelation == 'inModel') {
            return '$/' . strtolower($this->w_author) . '/' . strtolower($this->w_plugin) . '/models/' . strtolower(str_singular($key));
        } else {
            $parts = explode('.', $value);
            $r_plugin = array_pop($parts);
            $r_author = array_pop($parts);
            return '$/' . strtolower($r_author) . '/' . strtolower($r_plugin) . '/models/' . strtolower(str_singular($key));
        }
    }

    public function getRelationOptions($value)
    {
        if (!$value) {
            return null;
        }
        $parts = explode(',', $value);

        $options = [];

        //travail sur les deifferents coules key attribute
        foreach ($parts as $part) {
            $key_att = explode('.', $part);
            $options[$key_att[0]] = $key_att[1];
        }
        return $options;
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
        if ($this->files->exists($destinationFile) && !$this->option('force')) {
            throw new \Exception('Stop everything!!! This file already exists: ' . $destinationFile);
        }

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
            ['v', null, InputOption::VALUE_REQUIRED, 'Crée un update de version'],
            ['option', null, InputOption::VALUE_NONE, 'Crée uniquement le model'],
            ['file', null, InputOption::VALUE_REQUIRED, 'Fichier'],
        ];
    }
}
