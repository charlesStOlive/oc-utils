<?php namespace Waka\Utils\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Mexitek\PHPColors\Color;
use Twig;
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
        'ui_less/vars.stub' => '/assets/css/simple_grid/vars.less',
    ];

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        //$this->vars = $this->processVars($this->prepareVars());
        $this->vars = $this->prepareVars();

        //trace_log($this->vars);

        $this->makeStubs();

        $sourceFilePath = $this->getSourcePath() . '/../../../../modules/system/assets/ui/less';

        $files = \File::allFiles($sourceFilePath);
        foreach($files as $file) {
            $filePath = $file->getRealPath();
            $stringContent = $file->getContents();
            $stringContent = $this->updateColorContent($stringContent);
            $this->files->put($filePath, $stringContent);
        }

        $this->info($this->type . ' created successfully.');

        $this->call('winter:util', ['name' => 'compile less']);
    }

    public function updateColorContent($content) {
        $replaceColors = $this->vars['replace'];
        foreach($replaceColors as $key=>$color) {
            $content = str_replace($key, $color, $content);
        }
        return $content;

    }

    protected function getSourcePath()
    {
        $className = get_class($this);
        $class = new \ReflectionClass($className);

        return dirname($class->getFileName());
    }

    public function makeStub($stubName)
    {
        //trace_log($stubName);
        if (!isset($this->stubs[$stubName])) {
            return;
        }

        $sourceFile = $this->getSourcePath() . '/' . $stubName;
        $destinationFile = $this->getDestinationPath() . '/' . $this->stubs[$stubName];
        $destinationContent = $this->files->get($sourceFile);

        /*
         * Parse each variable in to the destination content and path
         */
        $destinationContent = Twig::parse($destinationContent, $this->vars);
        $destinationFile = Twig::parse($destinationFile, $this->vars);

        $this->makeDirectory($destinationFile);

        /*
         * Make sure this file does not already exist
         */
        if ($this->files->exists($destinationFile)) {
            $create = $this->ask('Recréer le fichier var.less', true);
            if(!$create) {
                return;
            }
        }
        //trace_log($destinationFile);
        //trace_log($destinationContent);

        $this->files->put($destinationFile, $destinationContent);
    }

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {

        $primaryColor = new Color(\Config::get('wcli.wconfig::brand_data.primaryColor'));
        $secondaryColor = new Color(\Config::get('wcli.wconfig::brand_data.secondaryColor'));
        $accentColor = new Color(\Config::get('wcli.wconfig::brand_data.accentColor'));

        $primary  = '#'.$primaryColor->getHex();
        $primary_dark =  '#'.$primaryColor->darken(10);
        $primary_light = '#'.$primaryColor->lighten(10);
        $primary_light2 = '#'.$primaryColor->lighten(20);
        $secondary = '#'.$secondaryColor->getHex();
        $accent = '#'.$accentColor->getHex();
        $accent_light = '#'.$accentColor->lighten(10);

        $replaceColors = [];

        $oldColors = \Config::get('wcli.wconfig::brand_data.oldColors');
        if($oldColors) {
            $old_primaryColor = new Color($oldColors['primaryColor']);
            $old_secondaryColor = new Color($oldColors['secondaryColor']);
            $old_accentColor = new Color($oldColors['accentColor']);

            $old_primary  = '#'.$old_primaryColor->getHex();
            $old_primary_dark =  '#'.$old_primaryColor->darken(10);
            $old_primary_light = '#'.$old_primaryColor->lighten(10);
            $old_primary_light2 = '#'.$old_primaryColor->lighten(20);
            $old_secondary = '#'.$old_secondaryColor->getHex();
            $old_accent = '#'.$old_accentColor->getHex();
            $old_accent_light = '#'.$old_accentColor->lighten(10);

            $replaceColors = [
               $old_primary_dark => $primary_dark,
               $old_primary => $primary,
               $old_primary_light => $primary_light,
               $old_accent_light => $accent_light,
               $old_primary_light2 => $primary_light2,
               $old_accent => $accent,
               $old_secondary => $secondary,
               $old_secondary => $secondary,
            ];

        } else {
             $replaceColors = [
               '#34495e' => $primary_dark,
               '#0181b9' => $primary,
               '#1681BA' => $primary,
               '#1F99DC' => $primary_light,
               '#3498db' => $accent_light,
               '#4da7e8' => $primary_light2,
               '#da5700' => $accent,
               '#405261' => $secondary,
               '#e67e22' => $accent_light,
            ];

        }

        //BrandPrimary 34495e

        //Primary button/link 0181b9 balloon 1681BA  checkbox : 1F99DC  brand-accentPRIMARY/3498db  active-bg/4da7e8

        //Accent da5700

        //secondary : 405261 (btn_sec)  brand-secondaryACCENT:e67e22

        //other/default bcc3c7 (defaut balloon)

        return [
            'primary' => $primary,
            'secondary' => $secondary,
            'accent' => $accent,
            'primary_dark' => $primary_dark,
            'primary_light' => $primary_light,
            'primary_light2' => $primary_light2,
            'replace' => $replaceColors,
        ];

        
        

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
