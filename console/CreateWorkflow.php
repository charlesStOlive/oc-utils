<?php namespace Waka\Utils\Console;

use October\Rain\Scaffold\GeneratorCommand;
use October\Rain\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Twig;

class CreateWorkflow extends GeneratorCommand
{
    public $wk_pluginCode;
    public $Wk_name;
    public $wk_plugin;
    public $wk_author;
    public $wk_model;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:workflow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new workflow';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Yaml';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */

    protected $stubs = [
        'workflow/workflow.stub' => 'config/{{lower_name}}_w.yaml',
        'workflow/temp_lang.stub' => 'lang/fr/{{lower_name}}_w.php',
    ];

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $this->vars = $this->processVars($this->prepareVars());

        $this->makeStubs();

        $this->info($this->type . 'created successfully.');

        $this->call('waka:dumpWorkflow', [
            'workflowName' => $this->wk_name,
            'plugin' => $this->wk_pluginCode,
            'model' => $this->wk_model,
        ]);
    }

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {
        $this->wk_name = $name = $this->argument('name');

        $this->wk_pluginCode = $pluginCode = $this->argument('plugin');
        $parts = explode('.', $pluginCode);
        $this->wk_plugin = $plugin = array_pop($parts);
        $this->wk_author = $author = array_pop($parts);

        $this->wk_model = $model = $this->argument('model');

        $fileName = 'workflow';

        if ($this->option('file')) {
            $fileName = $this->option('file');
        }

        $importExcel = new \Waka\Utils\Classes\Imports\ImportWorkflow($name);
        \Excel::import($importExcel, plugins_path('waka/wconfig/updates/files/' . $fileName . '.xlsx'));
        $rows = new Collection($importExcel->data->data);
        $config = new Collection($importExcel->config->data);
        $configs = $config->toArray();

        $rules = $config->where('type', '==', 'rules');
        $rules = $rules->map(function ($item, $key) {
            if ($item['type'] == 'rules') {
                $item['data'] = explode(',', $item['data']);
            }
            return $item;
        });

        $ruleSetArray = $config->where('type', '==', 'ruleset_name')->first();
        $ruleSetArray = explode(',', $ruleSetArray['value']);
        $rulesSets = [];
        foreach ($ruleSetArray as $ruleSetRowKey) {
            $set = $rules->filter(function ($item) use ($ruleSetRowKey) {
                return in_array($ruleSetRowKey, $item['data']);
            });
            $rulesSets[$ruleSetRowKey] = $set->lists('value', 'key');
        }
        //
        $trads = $config->where('type', '==', 'lang')->lists('data', 'key');
        //
        //
        $rows = $rows->map(function ($item, $key) {
            if ($item['type'] != 'trans') {
                return $item;
            }
            $varExiste = $îtem['var'] ?? false;
            if (empty($item['var'])) {
                $item['var'] = $item['from'] . '_to_' . $item['to'];
            }
            $item['functions'] = [];

            $fncProd = $item['fnc_prod'] ?? false;
            //trace_log("fncProd : " . $fncProd);
            if (!empty($fncProd)) {
                //trace_log("Travail sur les fonctions de production");
                $fncName = $item['fnc_prod'];
                $args = $item['fnc_prod_arg'] ?? false;
                if ($args) {
                    $args = explode(',', $args);
                }
                $vals = $item['fnc_prod_val'] ?? false;
                if ($vals) {
                    $vals = explode(',', $vals);
                }
                $argval = [];
                if (is_countable($args)) {
                    for ($i = 0; $i < count($args); $i++) {
                        $argval[$args[$i]] = $vals[$i] ?? null;

                    }
                }

                $obj = [
                    'fnc' => $fncName,
                    'type' => 'prod',
                    'arguments' => $argval,
                ];
                $item['functions'][$fncName] = $obj;

            }
            $fncTrait = $item['fnc_trait'] ?? false;
            //trace_log("fnctrait : " . $fncTrait);
            if (!empty($fncTrait)) {
                //trace_log("Travail sur les fonctions de production");
                $fncName = $item['fnc_trait'];
                $args = $item['fnc_trait_arg'] ?? false;
                if ($args) {
                    $args = explode(',', $args);
                }
                $vals = $item['fnc_trait_val'] ?? false;
                if ($vals) {
                    $vals = explode(',', $vals);
                }
                $argval = [];
                if (is_countable($args)) {
                    for ($i = 0; $i < count($args); $i++) {
                        $argval[$args[$i]] = $vals[$i] ?? null;

                    }
                }

                $obj = [
                    'fnc' => $fncName,
                    'type' => 'trait',
                    'arguments' => $argval,
                ];
                $item['functions'][$fncName] = $obj;

            }
            return $item;
        });

        $tradPlaces = $rows->where('lang', '<>', null)->where('type', '==', 'places')->lists('lang', 'var');
        $tradPlacesCom = $rows->where('com', '<>', null)->where('type', '==', 'places')->lists('com', 'var');
        $tradTrans = $rows->where('lang', '<>', null)->where('type', '==', 'trans')->lists('lang', 'var');
        $tradTransCom = $rows->where('com', '<>', null)->where('type', '==', 'trans')->lists('com', 'var');

        $places = $rows->where('type', '==', 'places')->toArray();
        $trans = $rows->where('type', '==', 'trans')->toArray();

        //trace_log($trans);

        $all = [
            'name' => $name,
            'model' => $model,
            'author' => $author,
            'plugin' => $plugin,
            'configs' => $configs,
            'trads' => $trads,
            'tradPlaces' => $tradPlaces,
            'tradTrans' => $tradTrans,
            'tradPlacesCom' => $tradPlacesCom,
            'tradTransCom' => $tradTransCom,
            'places' => $places,
            'trans' => $trans,
            'rulesSets' => $rulesSets,
        ];

        return $all;

        // if ($config['relation'] ?? false) {
        //     $array = explode(',', $config['relation']);
        //     $relationName = array_pop($array);
        //     $pluginRelationName = array_pop($array);
        // }

        // $rows = $rows->map(function ($item, $key) {
        //     $trigger = $item['trigger'] ?? null;
        //     if ($trigger) {
        //         if (starts_with($trigger, '!')) {
        //             $item['trigger'] = [
        //                 'field' => str_replace('!', "", $trigger),
        //                 'action' => 'hide',
        //             ];
        //         } else {
        //             $item['trigger'] = [
        //                 'field' => $trigger,
        //                 'action' => 'show',
        //             ];
        //         }
        //     }
        //     $options = $item['field_options'] ?? null;
        //     if ($options) {
        //         $array = explode(',', $options);
        //         $item['field_options'] = $array;
        //     }
        //     $relation = $item['relation'] ?? null;
        //     if ($relation && str_contains($relation, ',parent')) {
        //         $array = explode(',', $relation);
        //         $relationName = array_pop($array);
        //         $pluginRelationName = array_pop($array);
        //         $relation = [
        //             'relation_name' => $item['var'],
        //             'relation_class' => camel_case($item['var']),
        //             'plugin_name' => $pluginRelationName,
        //         ];
        //         $item['belong'] = $relation;
        //     } elseif ($relation && str_contains($relation, ',user')) {
        //         $array = explode(',', $relation);
        //         $relationName = array_pop($array);
        //         $relation = [
        //             'relation_name' => $item['var'],
        //             'relation_class' => 'Backend\Models\User',
        //             'type' => 'user',
        //         ];
        //         $item['belong'] = $relation;
        //     } elseif ($relation) {
        //         $array = explode(',', $relation);
        //         $relationName = array_pop($array);
        //         $pluginRelationName = array_pop($array);
        //         $relation = [
        //             'relation_name' => $relationName,
        //             'relation_class' => camel_case($relationName),
        //             'plugin_name' => $pluginRelationName,
        //         ];
        //         $item['hasmany'] = $relation;
        //     }
        //     $field_type = $item['field_type'] ?? null;
        //     if ($field_type == 'attachMany') {
        //         $item['attachMany'] = [
        //             'relation_name' => $item['var'],
        //             'relation_class' => 'System\Models\File',
        //         ];
        //     }
        //     if ($field_type == 'attachOne') {
        //         $item['attachOne'] = [
        //             'relation_name' => $item['var'],
        //             'relation_class' => 'System\Models\File',
        //         ];
        //     }
        //     return $item;

        // });

        // $config['belong'] = $rows->where('belong', '!=', null)->pluck('belong')->toArray();
        // $config['hasmany'] = $rows->where('hasmany', '!=', null)->pluck('hasmany')->toArray();
        // $config['attachOne'] = $rows->where('attachOne', '!=', null)->pluck('attachOne')->toArray();
        // $config['attachMany'] = $rows->where('attachMany', '!=', null)->pluck('attachMany')->toArray();
        // $config['lists'] = $rows->where('lists', '!=', null)->pluck('lists')->toArray();

        // /**/trace_log($rows->toArray());
        // /**/trace_log($config);

        // $trads = $rows->where('name', '<>', null)->toArray();

        // $dbs = $rows->where('type', '<>', null)->toArray();

        // $columns = $rows->where('column', '<>', null)->toArray();
        // $fields = $rows->where('field', '<>', null)->toArray();
        // $attributes = $rows->where('attribute', '<>', null)->toArray();

        // $tabs = [];
        // foreach ($config as $key => $value) {
        //     if (starts_with($key, 'tab::')) {
        //         $key = str_replace('tab::', "", $key);
        //         $tabs[$key] = $value;
        //     }

        // }

        // $excels = $rows->where('excel', '<>', null)->toArray();

        // $titles = $rows->where('title', '<>', null)->pluck('name', 'var')->toArray();
        // $appends = $rows->where('append', '<>', null)->pluck('name', 'var')->toArray();
        // $dates1 = $rows->where('type', '==', 'date');
        // $dates2 = $rows->where('type', '==', 'timestamp');
        // $dates = $dates1->merge($dates2)->pluck('name', 'var')->toArray();
        // $requireds = $rows->where('required', '<>', null)->pluck('required', 'var')->toArray();
        // $jsons = $rows->where('json', '<>', null)->pluck('json', 'var')->toArray();
        // $getters = $rows->where('getter', '<>', null)->pluck('json', 'var')->toArray();

        // if ($maker['model']) {
        //     /**/trace_log('on fait le modele');
        //     $this->stubs['model/model.stub'] = 'models/{{studly_name}}.php';
        // }
        // if ($maker['update']) {
        //     /**/trace_log('on fait le migrateur du modele');
        //     $this->stubs['model/create_table.stub'] = 'updates/create_{{snake_plural_name}}_table.php';
        // }
        // if ($maker['lang_field_attributes']) {
        //     /**/trace_log('on fait les langues fields et attributs');
        //     $this->stubs['model/attributes.stub'] = 'models/{{lower_name}}/attributes.yaml';
        //     $this->stubs = array_merge($this->stubs, $this->modelYamlstubs);
        //     $this->stubs['model/temp_lang.stub'] = 'lang/fr/{{lower_name}}.php';
        //     if ($config['use_tab']) {
        //         unset($this->stubs['model/fields.stub']);
        //         $this->stubs['model/fields_tab.stub'] = 'models/{{lower_name}}/fields.yaml';
        //     }
        // }

        // if ($maker['controller']) {
        //     /**/trace_log('on fait le controlleur et les configs');
        //     $this->stubs = array_merge($this->stubs, $this->controllerPhpStubs);
        //     if ($config['behav_duplicate']) {
        //         $this->stubs['controller/config_duplicate.stub'] = 'controllers/{{lower_ctname}}/config_duplicate.yaml';
        //     }
        //     if ($config['side_bar_attributes']) {
        //         $this->stubs['controller/config_attributes.stub'] = 'controllers/{{lower_ctname}}/config_attributes.yaml';
        //     }
        //     if ($config['side_bar_info']) {
        //         $this->stubs['controller/config_sidebar_info.stub'] = 'controllers/{{lower_ctname}}/config_sidebar_info.yaml';
        //     }
        //     if ($config['behav_lots']) {
        //         $this->stubs['controller/config_lots.stub'] = 'controllers/{{lower_ctname}}/config_lots.yaml';
        //     }

        //     if ($config['hasmany']) {
        //         foreach ($config['hasmany'] as $relation) {
        //             $this->makeOneStub('controller/_field_relation.stub', 'controllers/' . strtolower($model) . 's/_field_{{relation_name}}s.htm', $relation);
        //             $this->makeOneStub('model/fields_for.stub', 'models/' . $relation['relation_name'] . '/fields_for_' . strtolower($model) . '.yaml', []);
        //             $this->makeOneStub('model/columns_for.stub', 'models/' . $relation['relation_name'] . '/columns_for_' . strtolower($model) . '.yaml', []);
        //             // $this->stubs['model/fields_for.stub']  = 'models/{{relation_name}}/fields_for_{{lower_name}}.yaml';
        //             // $this->stubs['model/columns_for.stub'] = 'models{{relation_name}}/columns_for_{{lower_name}}.yaml';
        //         }
        //         $this->stubs['controller/config_relation.stub'] = 'controllers/{{lower_ctname}}/config_relation.yaml';
        //     }

        // }
        // if ($maker['html_file_controller']) {
        //     $this->stubs = array_merge($this->stubs, $this->controllerHtmStubs);
        //     if ($config['side_bar_attributes'] || $config['side_bar_info']) {
        //         unset($this->stubs['controller/update.stub']);
        //         $this->stubs['controller/update_sidebar.stub'] = 'controllers/{{lower_ctname}}/update.htm';
        //     }
        //     if ($config['behav_reorder']) {
        //         $this->stubs['controller/reorder.stub'] = 'controllers/{{lower_ctname}}/reorder.htm';
        //         $this->stubs['controller/config_reorder.stub'] = 'controllers/{{lower_ctname}}/config_reorder.yaml';
        //     }
        // }

        // if ($maker['excel']) {
        //     $this->stubs['imports/import.stub'] = 'classes/imports/{{studly_ctname}}Import.php';
        // }

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
            ['name', InputArgument::REQUIRED, 'The name of the workflow'],
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
            ['option', null, InputOption::VALUE_NONE, 'Crée uniquement le model'],
            ['file', null, InputOption::VALUE_REQUIRED, 'Fichier'],
        ];
    }
}
