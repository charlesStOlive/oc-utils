<?php

namespace Waka\Utils\Console;

use Config;
use Exception;
use System\Console\BaseScaffoldCommand;
use Symfony\Component\Process\Process;
use Waka\Utils\Classes\GraphvizDumper;
//use Symfony\Component\Workflow\Dumper\GraphvizDumper;
use Workflow;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class WorkflowDump extends BaseScaffoldCommand
{


    protected static $defaultName = 'waka:workflowDump';

    /**
     * @var string The name and signature of this command.
     */
    protected $signature =  'waka:workflowDump
        {workflowName : The name of the workflow. <info>(eg: Winter.Blog)</info>}
        {plugin : The name of the plugin. <info>(eg: Winter.Blog)</info>}
        {model : The name of the model. <info>(eg: ImportPosts)</info>}';




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

        //trace_log($config[$workflowName]['supports']);
        //trace_log($class);


        if (false === array_search($class, $config[$workflowName]['supports'])) {
            throw new Exception("Workflow $workflowName has no support for class $class." .
                ' Please specify a valid support class with the --class option.');
        }

        $subject = new $class;
        $workflow = Workflow::get(
            $subject,
            $workflowName
        );
        $definition = $workflow->getDefinition();

        $dumper = new GraphvizDumper();

        //Verification si le dossier image existe pour la doc. 
        $path = plugins_path(strtolower($author) . '/' . strtolower($plugin) . '/assets/docs_images');
        \File::isDirectory($path) or \File::makeDirectory($path, 0777, true, true);






        $dotCommand = $this->createDotCommand($workflowName, 'tb', 'jpeg');
        //trace_log($dotCommand);
        $tdOptions = ["graph" => ['rankdir' => 'TB']];
        $process = new Process($dotCommand);
        $process->setWorkingDirectory($path);
        $process->setInput($dumper->dump($definition, null, $tdOptions));

        //trace_log($dumper->dump($definition, null, $tdOptions));
        $process->mustRun();

        // $dumper = new GraphvizDumper();

        // $path = plugins_path(strtolower($author).'/'.strtolower($plugin).'/assets/docs_images');

        // $dotCommand = $this->createDotCommand($workflowName, 'tb', 'jpeg');

        // $process = new Process($dotCommand);
        // $process->setWorkingDirectory($path);
        // $process->setInput($dumper->dump($definition));
        // $process->mustRun();
    }

    public function createDotCommand($workflowSlug, $type, $format)
    {
        // Spécifiez la taille de la mémoire allouée à Graphviz
        $memory_limit = '2G';

        // Définissez la commande pour générer le graphique
        $command = ['dot', "-T${format}", '-o', "${workflowSlug}_${type}.${format}"];

        // Ajouter l'option de mémoire si la plate-forme actuelle est Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command[] = "-Gmemory_limit=${memory_limit}";
        } else {
            $command[] = "-Gmemory_limit=${memory_limit / 1000000}m";
        }

        return $command;
    }
}
