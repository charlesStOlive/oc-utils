<?php namespace Waka\Utils\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateBehaviorContent extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:behaviorcontent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new Behavior Content.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Injector';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'contentbehavior/behavior.stub'        => 'behaviors/Content{{studly_name}}.php',
    ];
    

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {
        $pluginCode = $this->argument('plugin');

        $parts = explode('.', $pluginCode);
        $plugin = array_pop($parts);
        $author = array_pop($parts);

        $code = $this->argument('code');

        return [
            'name' => $code,
            'author' => $author,
            'plugin' => $plugin,
        ];
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
            ['code', InputArgument::REQUIRED, 'The name of the code in blocType. Eg: Textes'],
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
        ];
    }
}
