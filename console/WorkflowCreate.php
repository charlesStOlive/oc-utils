<?php namespace Waka\Utils\Console;

use October\Rain\Scaffold\GeneratorCommand;
use October\Rain\Support\Collection;
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
        $this->vars = $this->prepareVars(true);

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

        $fileName = 'workflow';

        if ($this->option('file')) {
            $fileName = $this->option('file');
        }

        $importExcel = new \Waka\Utils\Classes\Imports\ImportWorkflow($name);
        \Excel::import($importExcel, plugins_path('waka/wconfig/updates/files/' . $fileName . '.xlsx'));
        $rows = new Collection($importExcel->data->data);
        $config = new Collection($importExcel->config->data);

        $data = [
            'putTrans' => $putTrans,
            'pluginCode' => $this->wk_pluginCode,
            'plugin' => $this->wk_plugin,
            'author' => $this->wk_author,
            'model' => $this->wk_model,
            'rows' => $rows,
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
