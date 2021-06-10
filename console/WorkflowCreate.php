<?php namespace Waka\Utils\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Winter\Storm\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Twig;

class WorkflowCreate extends GeneratorCommand
{
    public $wk_pluginCode;
    public $Wk_name;
    public $wk_plugin;
    public $wk_author;
    public $wk_model;
    public $remover;

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
        'workflow/wf_errors.stub' => 'lang/fr/{{lower_name}}_wf_errors.php',
        'workflow/listener.stub' => 'listeners/Workflow{{lower_name}}Listener.php',
    ];

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $this->vars = $this->prepareVars(true);

        //trace_log("handle");

        $this->makeStubs();

        $this->info($this->type . 'created successfully.');

        $this->call('waka:workflowDump', [
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
    protected function prepareVars($putTrans = false)
    {
        $this->wk_name = $name = $this->argument('name');

        $this->wk_pluginCode = $pluginCode = $this->argument('plugin');
        $parts = explode('.', $pluginCode);
        $this->wk_plugin = $plugin = array_pop($parts);
        $this->wk_author = $author = array_pop($parts);

        $this->wk_model = $model = $this->argument('model');

        $fileName = 'start';

        if ($this->argument('src')) {
            $fileName = $this->argument('src');
        }
        $startPath = null;
        if($this->wk_author == 'waka') {
            $startPath = env('SRC_WAKA');
        } 
        if($this->wk_author == 'wcli') {
            //trace_log(env('SRC_WCLI','merde'));
            $startPath = env('SRC_WCLI');
        }

        $filePath =  $startPath.'/'.$fileName.'.xlsx';
        //trace_log($filePath);

        if ($this->option('option')) {
            $this->remover = [
                'workflow/workflow.stub' => true,
                'workflow/temp_lang.stub' => true,
                'workflow/wf_errors.stub' => true,
                'workflow/listener.stub' => true,
            ];
            $types = $this->choice('Stub à creer', ['workflow/workflow.stub', 'workflow/temp_lang.stub', 'workflow/wf_errors.stub', 'workflow/listener.stub'], 0, null, true);
            //trace_log($types);
            foreach ($types as $type) {
                $this->remover[$type] = false;
            }
            foreach ($this->remover as $key => $remove) {
                if ($remove) {
                    unset($this->stubs[$key]);
                }
            }
        } else {
           unset($this->stubs['workflow/listener.stub']); 
        }

        $importExcel = new \Waka\Utils\Classes\Imports\ImportWorkflow($name);
        \Excel::import($importExcel, $filePath);
        $places = new Collection($importExcel->places->data);
        $trans = new Collection($importExcel->trans->data);
        $config = new Collection($importExcel->config->data);

        $data = [
            'putTrans' => $putTrans, //Active la traduction ou pas des codes de place et de transition
            'pluginCode' => $this->wk_pluginCode,
            'plugin' => $this->wk_plugin,
            'author' => $this->wk_author,
            'model' => $this->wk_model,
            'places' => $places,
            'trans' => $trans,
            'config' => $config,
        ];


        $prepareExcel = new \Waka\Utils\Classes\CreateWorkflowDataFromExcel();
        return $prepareExcel->prepareVars($data);
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
