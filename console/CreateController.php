<?php namespace Waka\Utils\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use October\Rain\Support\Str;

class CreateController extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new controller.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'controller/_list_toolbar.stub' => 'controllers/{{lower_name}}/_list_toolbar.htm',
        'controller/config_form.stub'   => 'controllers/{{lower_name}}/config_form.yaml',
        'controller/config_list.stub'   => 'controllers/{{lower_name}}/config_list.yaml',
        'controller/create.stub'        => 'controllers/{{lower_name}}/create.htm',
        'controller/index.stub'         => 'controllers/{{lower_name}}/index.htm',
        'controller/preview.stub'       => 'controllers/{{lower_name}}/preview.htm',
        'controller/update.stub'        => 'controllers/{{lower_name}}/update.htm',
        'controller/controller.stub'    => 'controllers/{{studly_name}}.php',
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

        $controller = $this->argument('controller');

        /*
         * Determine the model name to use,
         * either supplied or singular from the controller name.
         */
        $model = $this->option('model');
        if (!$model) {
            $model = Str::singular($controller);
        }
        /**
         * Hook Charles
         */
        $fireViewEvents = $this->ask('Fire view Events  ?', true);
        $addDuplicateBehavior = $this->ask('Add duplicate behavior  ?', false);
        $addSideBar = $this->ask('Add SideBar to Update layout  ?', false);
        $addReorderBehavior = $this->ask('Add Reorder  ?', false);

        if($addDuplicateBehavior) {
            $this->stubs['controller/config_duplicate.stub'] = 'controllers/{{lower_name}}/config_duplicate.yaml';
        }
        if($addSideBar) {
            unset($this->stubs['controller/update.stub']); 
            $this->stubs['controller/update_sidebar.stub'] = 'controllers/{{lower_name}}/update.htm';
        }
        if($addReorderBehavior) {
            $this->stubs['controller/reorder.stub'] = 'controllers/{{lower_name}}/reorder.htm';
            $this->stubs['controller/config_reorder.stub'] = 'controllers/{{lower_name}}/config_reorder.yaml';
        }

        return [
            'name' => $controller,
            'model' => $model,
            'author' => $author,
            'plugin' => $plugin,
            'fireViewEvents'=> $fireViewEvents,
            'addDuplicateBehavior' => $addDuplicateBehavior,
            'addSideBar' => $addSideBar,
            'addReorderBehavior' => $addReorderBehavior,
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
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin to create. Eg: RainLab.Blog'],
            ['controller', InputArgument::REQUIRED, 'The name of the controller. Eg: Posts'],
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
            ['model', null, InputOption::VALUE_OPTIONAL, 'Define which model name to use, otherwise the singular controller name is used.'],
        ];
    }
}
