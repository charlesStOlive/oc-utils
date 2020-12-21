<?php namespace Waka\Utils\Console;

use Config;
use Illuminate\Console\Command;
use October\Rain\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Twig;
use Waka\Utils\Classes\GraphvizDumper;
use Workflow;
// use Symfony\Component\Console\Input\InputArgument;
// use Symfony\Component\Console\Input\InputOption;
use Yaml;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class WorkflowOnlineDump extends WorkflowCreate
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:workflowOdump';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GraphvizDumper dumps a workflow as a graphviz file.
    You can convert the generated dot file with the dot utility (http://www.graphviz.org/):';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */

    protected $stubs = [
        'workflow/workflow.stub' => 'workflow.yaml',
        'workflow/description.stub' => 'workflow.yaml',
    ];

    public function makeStubs()
    {
        $workflow = [];
        $stubs = array_keys($this->stubs);

        foreach ($stubs as $stub) {
            $workflow[$stub] = $this->makeStub($stub);
        }
        return $workflow;
    }

    /**
     * Make a single stub.
     *
     * @param string $stubName The source filename for the stub.
     */
    public function makeStub($stubName)
    {
        if (!isset($this->stubs[$stubName])) {
            return;
        }
        $sourceFile = $this->getSourcePath() . '/' . $stubName;
        $destinationContent = $this->files->get($sourceFile);
        return Twig::parse($destinationContent, $this->vars);

    }

    public function handle()
    {
        $dotedClass = $this->argument('dotedClass');

        $parts = explode('.', $dotedClass);
        $r_author = $parts[0];
        $r_plugin = $parts[1];
        $r_model = $parts[2];

        $modelId = $this->argument('modelId');

        $callerClass = '\\' . $r_author . '\\' . $r_plugin . '\models\\' . $r_model;

        $srcModel = $callerClass::find($modelId);

        // trace_log($srcModel->name);
        // trace_log($srcModel->workflow_name);
        // trace_log($srcModel->src->getLocalPath());
        // trace_log(plugins_path('waka/wconfig/updates/files/workflow.xlsx'));

        $workflowName = $srcModel->workflow_name;
        $workflowSlug = $srcModel->slug;

        $importExcel = new \Waka\Utils\Classes\Imports\ImportWorkflow($workflowName);
        \Excel::import($importExcel, $srcModel->src->getLocalPath());
        $rows = new Collection($importExcel->data->data);
        $config = new Collection($importExcel->config->data);

        $data = [
            'putTrans' => false,
            'pluginCode' => null,
            'plugin' => "Utils",
            'author' => "Waka",
            'model' => "TestWf",
            'rows' => $rows,
            'config' => $config,
        ];

        $prepareExcel = new \Waka\Utils\Classes\CreateWorkflowDataFromExcel();
        $this->vars = $prepareExcel->prepareVars($data);
        $this->workflowData = $this->makeStubs();

        $class = '\Waka\Utils\Models\TestWf';

        $format = 'png';
        $registry = app()->make('workflow');
        $workflowDefinition = Yaml::parse($this->workflowData['workflow/workflow.stub']);
        //trace_log($workflowDefinition);
        $registry->addFromArray($workflowName, $workflowDefinition['testwf']);

        $subject = new $class;
        $workflow = Workflow::get(
            $subject, $workflowName);
        $definition = $workflow->getDefinition();

        $dumper = new GraphvizDumper();

        //trace_log($dumper);

        $dotCommand = $this->createDotCommand($workflowSlug, $format);

        $process = new Process($dotCommand);
        $option = $this->getModelOptions($srcModel->options);
        $process->setInput($dumper->dump($definition, null, $option));
        $coin = $process->mustRun();

        $srcModel->description = $this->workflowData['workflow/description.stub'];
        $srcModel->workflow = $this->workflowData['workflow/workflow.stub'];
        $this->tryCopyImage($srcModel, $format, 2);
        //trace_log("end");

    }

    public function createDotCommand($workflowSlug, $format)
    {
        return "dot -T$format -o " . storage_path('temp/' . $workflowSlug . '.' . $format);
    }

    public function tryCopyImage($srcModel, $format, $attemp)
    {
        $filePath = storage_path('temp/' . $srcModel->slug . '.' . $format);
        if (file_exists($filePath)) {
            $srcModel->image = $filePath;
            $srcModel->save();
        } elseif ($attemp > 0) {
            sleep(1);
            $attemp--;
            $this->tryCopyImage($srcModel, $format, $attemp);
        } else {
            $srcModel->save();
        }

    }
    public function getModelOptions($option)
    {
        //trace_log($option);
        switch ($option) {
            case 'ortho_LR':
                # code...
                return [
                    'graph' => ['splines' => 'ortho', 'rankdir' => 'LR'],
                ];
            case 'ortho_TD':
                # code...
                return [
                    'graph' => ['splines' => 'ortho', 'rankdir' => 'TD'],
                ];
            case 'curved_LR':
                # code...
                return [
                    'graph' => ['splines' => 'spline', 'rankdir' => 'LR'],
                ];
            case 'curved_TD':
                # code...
                return [
                    'graph' => ['splines' => 'spline', 'rankdir' => 'TD'],
                ];
            default:
                return [];
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['dotedClass', InputArgument::REQUIRED, 'The name of the workflow'],
            ['modelId', InputArgument::REQUIRED, 'The name of the workflow'],
        ];
    }
}
