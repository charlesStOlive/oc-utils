<?php namespace Waka\Utils\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PushData extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charger des données';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        // //pour les models
        // 'model/model.stub' => 'models/{{studly_name}}.php',
        // 'model/temp_lang.stub' => 'lang/fr/temp_{{lower_name}}.php',
        // 'model/fields.stub' => 'models/{{lower_name}}/fields.yaml',
        // 'model/columns.stub' => 'models/{{lower_name}}/columns.yaml',
        // 'model/create_table.stub' => 'updates/create_{{snake_plural_name}}_table.php',
        // //pour le controller
        // 'controller/_list_toolbar.stub' => 'controllers/{{lower_ctname}}/_list_toolbar.htm',
        // 'controller/config_form.stub' => 'controllers/{{lower_ctname}}/config_form.yaml',
        // 'controller/config_list.stub' => 'controllers/{{lower_ctname}}/config_list.yaml',
        // 'controller/create.stub' => 'controllers/{{lower_ctname}}/create.htm',
        // 'controller/index.stub' => 'controllers/{{lower_ctname}}/index.htm',
        // 'controller/preview.stub' => 'controllers/{{lower_ctname}}/preview.htm',
        // 'controller/update.stub' => 'controllers/{{lower_ctname}}/update.htm',
        // 'controller/controller.stub' => 'controllers/{{studly_ctname}}.php',

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
        $plugin = ucfirst(array_pop($parts));
        $author = ucfirst(array_pop($parts));

        $fileName = $this->ask('file_name');
        $className = $this->ask('class');
        $one_sheet = $this->ask('one_sheet');

        $class_name = ucfirst($this->argument('class'));

        $only_one = $this->option('sheet');

        $class_name = '\\' . $author . '\\' . $plugin . '\\Classes\\Imports\\' . $className;
        $ImportClass = new \ReflectionClass($one_sheet);
        \Excel::import($importExcel, storage_path('app/media/' . $fileName . '.xlsx'));
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
            ['sheet', null, InputOption::REQUIRED, 'Charger un seul onglet'],
        ];
    }
}
