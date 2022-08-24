<?php namespace Waka\Utils\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateRuleFnc extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:ruleFnc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new rule Fnc.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Fnc';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'rules/rule_fnc_fields.stub'        => 'wakarules/fncs/{{lower_name}}/fields.yaml',
        'rules/rule_fnc.stub'       => 'wakarules/fncs/{{studly_name}}.php',
    ];

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars(): array
    {
        $pluginCode = $this->argument('plugin');

        $parts = explode('.', $pluginCode);
        $plugin = array_pop($parts);
        $author = array_pop($parts);

        $model = $this->argument('model');

        return [
            'name' => $model,
            'author' => $author,
            'plugin' => $plugin
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
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin. Eg: Winter.Blog'],
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
