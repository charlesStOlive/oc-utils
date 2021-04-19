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
        'workflow/places.stub' => 'places.txt',
        'workflow/transitions.stub' => 'transitions.txt',
        'workflow/infos.stub' => 'transitions.txt',
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

        $callerClass = '\\' . ucfirst($r_author) . '\\' . ucfirst($r_plugin) . '\models\\' . ucfirst($r_model);

        $srcModel = $callerClass::find($modelId);

        // trace_log($srcModel->name);
        // trace_log($srcModel->workflow_name);
        // trace_log($srcModel->src->getLocalPath());
        // trace_log(plugins_path('wcli/wconfig/updates/files/workflow.xlsx'));

        $workflowName = $srcModel->workflow_name;
        $workflowSlug = $srcModel->slug;

        $importExcel = new \Waka\Utils\Classes\Imports\ImportWorkflow($workflowName);
        \Excel::import($importExcel, $srcModel->src->getLocalPath());
        $places = new Collection($importExcel->places->data);
        $trans = new Collection($importExcel->trans->data);
        $config = new Collection($importExcel->config->data);

        $data = [
            'putTrans' => false,
            'pluginCode' => null,
            'plugin' => "Utils",
            'author' => "Waka",
            'model' => "TestWf",
            'places' => $places,
            'trans' => $trans,
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
            $subject,
            $workflowName
        );
        $definition = $workflow->getDefinition();

        $dumper = new GraphvizDumper();

        //Création de l'image en 16/
        $dotCommand = $this->createDotCommand($workflowSlug, 'tb', $format);
        $tdOptions = ["graph" => ['rankdir' => 'TB']];
        $process = new Process($dotCommand);
        $process->setInput($dumper->dump($definition, null, $tdOptions));
        $process->mustRun();

        $dotCommand = $this->createDotCommand($workflowSlug, 'lr', $format);
        $lrOptions = ["graph" => ['rankdir' => 'LR']];
        $process = new Process($dotCommand);
        $process->setInput($dumper->dump($definition, null, $lrOptions));
        $process->mustRun();

        $srcModel->description = $this->workflowData['workflow/description.stub'];
        $srcModel->workflow = html_entity_decode($this->workflowData['workflow/workflow.stub'], ENT_QUOTES);
        $srcModel->places = html_entity_decode($this->workflowData['workflow/places.stub'], ENT_QUOTES);
        $srcModel->transitions = html_entity_decode($this->workflowData['workflow/transitions.stub'], ENT_QUOTES);
        $srcModel->infos = html_entity_decode($this->workflowData['workflow/infos.stub'], ENT_QUOTES);
        $this->tryCopyImage($srcModel, 'tb', $format, 2);
        $this->tryCopyImage($srcModel, 'lr', $format, 2);
    }

    public function createDotCommand($workflowSlug, $type, $format)
    {
        return "dot -T$format -o " . storage_path('temp/' . $workflowSlug . '_' . $type . '.' . $format);
    }

    public function tryCopyImage($srcModel, $type, $format, $attemp)
    {
        $filePath = storage_path('temp/' . $srcModel->slug . '_' . $type . '.' . $format);
        if (file_exists($filePath)) {
            $srcModel->{'image_' . $type} = $filePath;
            $srcModel->save();
        } elseif ($attemp > 0) {
            sleep(1);
            $attemp--;
            $this->tryCopyImage($srcModel, $type, $format, $attemp);
        } else {
            $srcModel->save();
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
