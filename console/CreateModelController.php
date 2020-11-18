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

    protected $controllerStubs = [
        'controller/_list_toolbar.stub' => 'controllers/{{lower_ctname}}/_list_toolbar.htm',
        'controller/config_form.stub'   => 'controllers/{{lower_ctname}}/config_form.yaml',
        'controller/config_list.stub'   => 'controllers/{{lower_ctname}}/config_list.yaml',
        'controller/create.stub'        => 'controllers/{{lower_ctname}}/create.htm',
        'controller/index.stub'         => 'controllers/{{lower_ctname}}/index.htm',
        'controller/preview.stub'       => 'controllers/{{lower_ctname}}/preview.htm',
        'controller/update.stub'        => 'controllers/{{lower_ctname}}/update.htm',
        'controller/controller.stub'    => 'controllers/{{studly_ctname}}.php',

    ];
    protected $stubs = [
        'model/model.stub'        => 'models/{{studly_name}}.php',
        'model/temp_lang.stub'    => 'lang/fr/{{lower_name}}.php',
        'model/fields.stub'       => 'models/{{lower_name}}/fields.yaml',
        'model/columns.stub'      => 'models/{{lower_name}}/columns.yaml',
        'model/create_table.stub' => 'updates/create_{{snake_plural_name}}_table.php',
    ];

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {
        $pluginCode = $this->argument('plugin');

        $parts  = explode('.', $pluginCode);
        $plugin = array_pop($parts);
        $author = array_pop($parts);

        $model = $this->argument('model');

        // $values = $this->ask('Coller des valeurs excels ', true);
        // trace_log($values);
        $importExcel = new \Waka\Utils\Classes\Imports\ImportModelController($model);
        \Excel::import($importExcel, plugins_path('waka/wconfig/updates/files/start.xlsx'));
        $rows   = new Collection($importExcel->data->data);
        $config = $importExcel->config->data;

        $relationName       = null;
        $pluginRelationName = null;

        // if ($config['relation'] ?? false) {
        //     $array = explode(',', $config['relation']);
        //     $relationName = array_pop($array);
        //     $pluginRelationName = array_pop($array);
        // }

        $rows = $rows->map(function ($item, $key) {
            $relation = $item['relation'] ?? null;
            if ($relation && str_contains($relation, ',parent')) {
                $array              = explode(',', $relation);
                $relationName       = array_pop($array);
                $pluginRelationName = array_pop($array);
                $relation           = [
                    'relation_name'  => $item['var'],
                    'relation_class' => camel_case($item['var']),
                    'plugin_name'    => $pluginRelationName,
                ];
                $item['belong'] = $relation;
                return $item;
            } elseif ($relation) {
                $array              = explode(',', $relation);
                $relationName       = array_pop($array);
                $pluginRelationName = array_pop($array);
                $relation           = [
                    'relation_name'  => $relationName,
                    'relation_class' => camel_case($relationName),
                    'plugin_name'    => $pluginRelationName,
                ];
                $item['hasmany'] = $relation;
                return $item;
            } else {
                return $item;
            }
        });

        $config['belong']  = $rows->where('belong', '!=', null)->pluck('belong')->toArray();
        $config['hasmany'] = $rows->where('hasmany', '!=', null)->pluck('hasmany')->toArray();
        $config['lists']   = $rows->where('lists', '!=', null)->pluck('lists')->toArray();

        // trace_log($rows->toArray());
        // trace_log($config);

        $trads = $rows->where('name', '<>', null)->pluck('name', 'var')->toArray();

        $dbs = $rows->where('type', '<>', null)->toArray();

        $columns = $rows->where('column', '<>', null)->toArray();
        $fields  = $rows->where('field', '<>', null)->toArray();

        $excels = $rows->where('excel', '<>', null)->toArray();

        $titles    = $rows->where('title', '<>', null)->pluck('name', 'var')->toArray();
        $appends   = $rows->where('append', '<>', null)->pluck('name', 'var')->toArray();
        $dates1    = $rows->where('type', '==', 'date');
        $dates2    = $rows->where('type', '==', 'timestamp');
        $dates     = $dates1->merge($dates2)->pluck('name', 'var')->toArray();
        $requireds = $rows->where('required', '<>', null)->pluck('required', 'var')->toArray();
        $jsons     = $rows->where('json', '<>', null)->pluck('json', 'var')->toArray();
        $getters   = $rows->where('getter', '<>', null)->pluck('json', 'var')->toArray();

        if (!$this->option('model')) {
            if ($config['behav_duplicate'] && !$this->option('model')) {
                $this->stubs['controller/config_duplicate.stub'] = 'controllers/{{lower_ctname}}/config_duplicate.yaml';
            }
            if ($config['side_bar'] && !$this->option('model')) {
                unset($this->stubs['controller/update.stub']);
                $this->stubs['controller/update_sidebar.stub'] = 'controllers/{{lower_ctname}}/update.htm';
            }
            if ($config['behav_reorder'] && !$this->option('model')) {
                $this->stubs['controller/reorder.stub']        = 'controllers/{{lower_ctname}}/reorder.htm';
                $this->stubs['controller/config_reorder.stub'] = 'controllers/{{lower_ctname}}/config_reorder.yaml';
            }

            if ($config['hasmany']) {
                foreach ($config['hasmany'] as $relation) {
                    $this->makeOneStub('controller/_field_relation.stub', 'controllers/' . strtolower($model) . 's/_field_{{relation_name}}s.htm', $relation);
                    $this->makeOneStub('model/fields_for.stub', 'models/' . $relation['relation_name'] . '/fields_for_' . strtolower($model) . '.yaml', []);
                    $this->makeOneStub('model/columns_for.stub', 'models/' . $relation['relation_name'] . '/columns_for_' . strtolower($model) . '.yaml', []);
                    // $this->stubs['model/fields_for.stub']  = 'models/{{relation_name}}/fields_for_{{lower_name}}.yaml';
                    // $this->stubs['model/columns_for.stub'] = 'models{{relation_name}}/columns_for_{{lower_name}}.yaml';
                }
                $this->stubs['controller/config_relation.stub'] = 'controllers/{{lower_ctname}}/config_relation.yaml';
            }
            $this->stubs = array_merge($this->stubs, $this->controllerStubs);
        }

        if ($excels) {
            $this->stubs['imports/import.stub'] = 'classes/imports/{{studly_ctname}}Import.php';
        }

        $all = [
            'name'            => $model,
            'ctname'          => $model . 's',
            'author'          => $author,
            'plugin'          => $plugin,
            'configs'         => $config,
            'trads'           => $trads,
            'dbs'             => $dbs,
            'columns'         => $columns,
            'fields'          => $fields,
            'titles'          => $titles,
            'appends'         => $appends,
            'dates'           => $dates,
            'requireds'       => $requireds,
            'jsons'           => $jsons,
            'getters'         => $getters,
            'excels'          => $excels,
            //
            'relation_name'   => $relationName,
            'relation_plugin' => $pluginRelationName,

        ];

        //trace_log($all);

        return $all;
    }

    protected function processVars($vars)
    {

        $cases     = ['upper', 'lower', 'snake', 'studly', 'camel', 'title'];
        $modifiers = ['plural', 'singular', 'title'];

        foreach ($vars as $key => $var) {
            if (!is_array($var) && $var) {
                /*
                 * Apply cases, and cases with modifiers
                 */
                foreach ($cases as $case) {
                    $primaryKey        = $case . '_' . $key;
                    $vars[$primaryKey] = $this->modifyString($case, $var);

                    foreach ($modifiers as $modifier) {
                        $secondaryKey        = $case . '_' . $modifier . '_' . $key;
                        $vars[$secondaryKey] = $this->modifyString([$modifier, $case], $var);
                    }
                }

                /*
                 * Apply modifiers
                 */
                foreach ($modifiers as $modifier) {
                    $primaryKey        = $modifier . '_' . $key;
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

        $sourceFile         = $this->getSourcePath() . '/' . $stubName;
        $destinationFile    = $this->getDestinationPath() . '/' . $destinationName;
        $destinationContent = $this->files->get($sourceFile);

        /*
         * Parse each variable in to the destination content and path
         */
        $destinationContent = Twig::parse($destinationContent, $tempVar);
        $destinationFile    = Twig::parse($destinationFile, $tempVar);

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
            ['model', null, InputOption::VALUE_NONE, 'Crée uniquement le model'],
        ];
    }
}
