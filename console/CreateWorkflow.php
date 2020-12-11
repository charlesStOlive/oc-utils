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
