<?php namespace Waka\Utils\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class CreateUiColors extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:uicolors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Ui Less files.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Less';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'ui_less/button.variables.stub' => '../../../modules/system/assets/ui/less/button.variables.less',
        'ui_less/checkbox.balloon.stub' => '../../../modules/system/assets/ui/less/checkbox.balloon.less',
        'ui_less/checkbox.stub' => '../../../modules/system/assets/ui/less/checkbox.less',
        'ui_less/global.variables.stub' => '../../../modules/system/assets/ui/less/global.variables.less',
        'ui_less/select.variables.stub' => '../../../modules/system/assets/ui/less/select.variables.less',
    ];

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $this->vars = $this->processVars($this->prepareVars());

        $this->makeStubs();

        $this->info($this->type . ' created successfully.');

        $this->call('october:util', ['name' => 'compile less']);
    }

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {
        $primary = $this->ask('primary');
        $secondary = $this->ask('secondary');

        return [
            'primary' => $primary,
            'secondary' => $secondary,
        ];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    // protected function getArguments()
    // {
    //     return [
    //         ['plugin', InputArgument::REQUIRED, 'The name of the plugin. Eg: RainLab.Blog'],
    //         ['model', InputArgument::REQUIRED, 'The name of the model. Eg: Post'],
    //     ];
    // }

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
