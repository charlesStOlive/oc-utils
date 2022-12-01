<?php namespace Waka\Utils\Console;

use System\Console\BaseScaffoldCommand;
use Winter\Storm\Support\Collection;
use Twig;

class WorkflowCreate extends BaseScaffoldCommand
{
    public $wk_pluginCode;
    public $Wk_name;
    public $wk_plugin;
    public $wk_author;
    public $wk_model;
    public $remover;

    protected static $defaultName = 'waka:workflow';

    /**
     * @var string The name and signature of this command.
     */
    protected $signature = 'waka:workflow
        {name : The name of the workflow. <info>(eg: Winter.Blog)</info>}
        {plugin : The name of the plugin. <info>(eg: Winter.Blog)</info>}
        {model : The name of the command to generate. <info>(eg: ImportPosts)</info>}
        {src : nom de la source excel. <info>(eg: start_wcms.xlsx)</info>}
        {--o|option : Afficher Les opptions}
        {--f|force : Overwrite existing files  with generated files.}';

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
        'workflow/description.stub' => 'docs/wf_{{lower_name}}.md',
    ];

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        //trace_log('je commence worrkflow create');
        $this->vars = $this->prepareVars(true);

        //trace_log("handle");

        $this->makeStubs();
        //trace_log('je fait les stubs');

        $this->info($this->type . 'created successfully.');
        //trace_log('je calll workflowDump');
        $this->call('waka:workflowDump', [
            'workflowName' => $this->wk_name,
            'plugin' => $this->wk_pluginCode,
            'model' => $this->wk_model,
        ]);
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
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars($putTrans = false):array
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
            $types = (array) $this->choice('Stub à creer', ['workflow/workflow.stub', 'workflow/temp_lang.stub', 'workflow/wf_errors.stub', 'workflow/listener.stub'], 0, null, true);
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
        //trace_log($filePath);
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
            'name' => $this->wk_name,
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

    
}
