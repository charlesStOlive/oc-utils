<?php namespace Waka\Utils\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateInjector extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:injector';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new Injector.';

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
        'injector/injector.stub'        => 'injectors/{{studly_name}}{{studly_targetName}}.php',
        'injector/field_target.stub'        => 'controllers/injectors/{{lower_targetName}}/_field_{{lower_name}}.htm',
        'injector/config_relation.stub'        => 'controllers/injectors/{{lower_targetName}}/config_relation.yaml',
        'injector/fields_for_relation.stub'        => 'models/{{lower_name}}/fields_for_relation_{{lower_targetName}}.yaml',
    ];
    

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars():array
    {
        $pluginCode = $this->argument('plugin');

        $parts = explode('.', $pluginCode);
        $plugin = array_pop($parts);
        $author = array_pop($parts);

        $model = $this->argument('model');

        $targetPlugin = $this->ask('Target plugin  ?');

        $targetParts = explode('.', $targetPlugin);
        $targetPlugin = array_pop($targetParts);
        $targetAuthor = array_pop($targetParts);

        $targetModel = $this->ask('Name of the target  model ?');

        return [
            'name' => $model,
            'author' => $author,
            'plugin' => $plugin,
            'targetPlugin' => $targetPlugin,
            'targetAuthor' => $targetAuthor,
            'targetName' => $targetModel
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
        ];
    }
}
