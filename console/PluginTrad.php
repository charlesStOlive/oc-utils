<?php namespace Waka\Utils\Console;

use October\Rain\Scaffold\GeneratorCommand;
use October\Rain\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Twig;

class PluginTrad extends GeneratorCommand
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
    protected $name = 'waka:plugintrad';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lancer la traduction de langues';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'lang';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */

    protected $stubs = [];

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $this->vars = $this->prepareVars(true);

        $this->makeTrads();

        $this->info($this->type . 'created successfully.');

    }

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars($putTrans = false)
    {
        $this->p_name = $name = $this->argument('name');

        $this->p_pluginCode = $pluginCode = $this->argument('plugin');
        $parts = explode('.', $pluginCode);
        $this->p_plugin = $plugin = array_pop($parts);
        $this->p_author = $author = array_pop($parts);

        $this->p_model = $model = $this->option('model');
    }

    /**
     * Make all stubs.
     *
     * @return void
     */
    public function makeTrads()
    {
        // $stubs = array_keys($this->stubs);

        // foreach ($stubs as $stub) {
        //     $this->makeStub($stub);
        // }
    }

    /**
     * Make a single stub.
     *
     * @param string $stubName The source filename for the stub.
     */
    public function makeStub($stubName)
    {
        // if (!isset($this->stubs[$stubName])) {
        //     return;
        // }

        // $sourceFile = $this->getSourcePath() . '/' . $stubName;
        // $destinationFile = $this->getDestinationPath() . '/' . $this->stubs[$stubName];
        // $destinationContent = $this->files->get($sourceFile);

        // /*
        //  * Parse each variable in to the destination content and path
        //  */
        // $destinationContent = Twig::parse($destinationContent, $this->vars);
        // $destinationFile = Twig::parse($destinationFile, $this->vars);

        // $this->makeDirectory($destinationFile);

        // /*
        //  * Make sure this file does not already exist
        //  */
        // if ($this->files->exists($destinationFile) && !$this->option('force')) {
        //     throw new Exception('Stop everything!!! This file already exists: ' . $destinationFile);
        // }

        // $this->files->put($destinationFile, $destinationContent);
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
            ['model', InputArgument::REQUIRED, 'The name of the model. Eg: Post'],
        ];
    }
}
