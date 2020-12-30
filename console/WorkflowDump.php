<?php namespace Waka\Utils\Console;

use Config;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Waka\Utils\Classes\GraphvizDumper;
use Workflow;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class WorkflowDump extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:workflowDump';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GraphvizDumper dumps a workflow as a graphviz file.
    You can convert the generated dot file with the dot utility (http://www.graphviz.org/):';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Png';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $pluginCode = $this->argument('plugin');
        $parts = explode('.', $pluginCode);

        $plugin = ucfirst(array_pop($parts));
        $author = ucfirst(array_pop($parts));

        $model = ucfirst($this->argument('model'));

        $class = '\\' . $author . '\\' . $plugin . '\Models\\' . $model;

        $workflowName = $this->argument('workflowName');
        $format = 'png';
        $config = Config::get('workflow');

        if (!isset($config[$workflowName])) {
            throw new Exception("Workflow $workflowName is not configured.");
        }

        if (false === array_search($class, $config[$workflowName]['supports'])) {
            throw new Exception("Workflow $workflowName has no support for class $class." .
                ' Please specify a valid support class with the --class option.');
        }

        $subject = new $class;
        $workflow = Workflow::get(
            $subject, $workflowName);
        $definition = $workflow->getDefinition();

        $dumper = new GraphvizDumper();

        //trace_log($dumper);

        $dotCommand = $this->createDotCommand($workflowName, 'td', 'jpeg');
        $tdOptions = ["graph" => ['rankdir' => 'TD']];
        $process = new Process($dotCommand);
        $process->setInput($dumper->dump($definition, null, $tdOptions));
        $process->mustRun();
    }

    public function createDotCommand($workflowSlug, $type, $format)
    {
        return "dot -T$format -o " . storage_path('temp/' . $workflowSlug . '_' . $type . '.' . $format);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['workflowName', InputArgument::REQUIRED, 'The name of the workflow'],
            ['plugin', InputArgument::REQUIRED, 'The doted class'],
            ['model', InputArgument::REQUIRED, 'The doted class'],
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
            ['class', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
        ];
    }
}
