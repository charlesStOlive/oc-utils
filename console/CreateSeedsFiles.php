<?php namespace Waka\Utils\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Winter\Storm\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Twig;
use Yaml;
use Brick\VarExporter\VarExporter;


class CreateSeedsFiles extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:createSeed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new seed files from database.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Seed';
    protected $mode;
    

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [];
    //
    public $pluginObj = [];
    public $relations;
    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $this->vars = $this->processVars($this->prepareVars());
        //Création du modele
         
        $this->makeStubs();

        $this->info($this->type . 'created successfully.');
    }

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
        $model = $this->argument('model');
        //
        $classes = $this->getModelsConfig();
        $classes = array_keys($classes);
        $this->classes = $this->choice('Class A absorber', $classes, 0, null, true);
        //
        return [
            'author' => $author,
            'plugin' => $plugin,
            'model' => $model,
            'version' => $this->argument('version'),
        ];

    }

    /**
     * Make a single stub.
     *
     * @param string $stubName The source filename for the stub.
     */
    

    public function makeStubs()
    {

        foreach ($this->classes as $classIndex) {
            $class = $this->getModelsConfig()[$classIndex];
            $this->createSeedFile($classIndex, $class);
        }
        return null;
    }

    public function createSeedFile($classIndex,$class) {
        $sourceFile = $this->getSourcePath() . '/seeds/seed.stub';
        $destinationFile = $this->getDestinationPath() . '/updates/Seed' . $this->vars['studly_model'].camel_case($classIndex).$this->vars['version'].'.php';
        //
        $className = $class['class'];
        $files = $class['files'] ?? [];
        $dataSourceName = $this->vars['lower_model'];
        $getAllDatas = $class['all'] ?? false;
        $datas = [];
        //Recherche des modèles existants : 
        if($getAllDatas) {
            $datas = $className::get();
        } else {
            $datas = $className::where('data_source', $dataSourceName)->get();
        }
        //Remove ID for each row
        $datas->transform(function ($item, $key) use ($files) {
            unset($item['id']);
            return $item;
        });
        //$datas = $datas->toArray();
        //Création des fichiers
        $finalDatas = [];
        foreach($datas as $key=>$data) {
            $inject = [];
            $subDatas = $data->toArray();
            foreach($subDatas as $key=>$subData) {
                if(is_array($subData)) {
                    $subDatas[$key] = json_encode($subData);
                }
            }
            $inject['w_dataString'] = VarExporter::export($subDatas,VarExporter::NO_CLOSURES,3);
            $inject['w_fileconfig'] = [];
            foreach($files as $filekey=>$file) {
                // On parocur la liste des fichier a copier
                $attributeName = $file['attribute'];
                if($data[$attributeName] ?? false) {
                    //Si on en trouve 1 on copie le fichier.
                    $path = $data->{$attributeName};
                    if($file['mode'] =='copyStore') {
                        $file['attributeValue'] = $path;
                        $path = storage_path('app/media'.$path);
                    } else if ($file['mode'] =='copyUpload') {
                        $path = $item->{$attributeName}->getPath();
                    }
                    //On ajoute les information du fichier dans le row de données. 
                   
                    //On continue la création.
                    $pathinfo = pathinfo($path);
                    $fileName = $pathinfo['basename'];
                    $file['srcPath'] = '/updates/files/'.$fileName;
                    $filePath = $this->getDestinationPath() . '/updates/files/'.$fileName;
                    $this->makeDirectory($filePath);
                    $fileContent = $this->files->get($path);
                    $this->files->put($filePath, $fileContent);
                    //
                    //$item['files'][$attributeName] = $file;
                    //
                }
                array_push($inject['w_fileconfig'],$file);

            }
            array_push($finalDatas, $inject);  
        }
        // $datas->transform(function ($item, $key) use($files, $finalDatas) {
            
        //     $subDatas = $item->toArray();
        //     foreach($subDatas as $key=>$subData) {
        //         if(is_array($subData)) {
        //             $subDatas[$key] = json_encode($subData);
        //         }
        //     }
        //     $item['w_dataString'] = VarExporter::export($subDatas,VarExporter::NO_CLOSURES,3);
        //     $item['w_fileconfig'] = [];
        //     //On parcours lesrequetes
        //     foreach($files as $filekey=>$file) {
        //         // On parocur la liste des fichier a copier
        //         $attributeName = $file['attribute'];
        //         if($item[$attributeName] ?? false) {
        //             //Si on en trouve 1 on copie le fichier.
        //             $path = $item[$attributeName];
        //             if($file['mode'] =='copyStore') {
        //                 $file['attributeValue'] = $path;
        //                 $path = storage_path('app/media'.$path);
        //             } else if ($file['mode'] =='copyUpload') {
        //                 $path = $item->{$attributeName}->getPath();
        //             }
        //             //On ajoute les information du fichier dans le row de données. 
                   
        //             //On continue la création.
        //             $pathinfo = pathinfo($path);
        //             $fileName = $pathinfo['basename'];
        //             $filePath = $this->getDestinationPath() . '/updates/files/'.$fileName;
        //             $this->makeDirectory($filePath);
        //             $fileContent = $this->files->get($path);
        //             $this->files->put($filePath, $fileContent);
        //             //
        //             //$item['files'][$attributeName] = $file;
        //             //
        //         }
        //         array_push($item['w_fileconfig'],[$file]);

        //     }

        //     return $item;
        // });
        // $datas = $datas->toArray();
        $destinationContent = $this->files->get($sourceFile);
        $seedVars = array_merge($this->vars, ['datas' => $finalDatas ], ['className' => $className]);
        trace_log($seedVars);
        $destinationContent = Twig::parse($destinationContent, $seedVars);
        $this->files->put($destinationFile, $destinationContent);

    }


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
        // $this->files->put($destinationFile, $destinationContent);
    }

        /**
     * Get the plugin path from the input.
     *
     * @return string
     */
    protected function getDestinationPath()
    {
        $plugin = $this->getPluginInput();

        $parts = explode('.', $plugin);
        $name = array_pop($parts);
        $author = array_pop($parts);

        return plugins_path(strtolower($author) . '/' . strtolower($name));
    }

    /**
     * Get the desired plugin name from the input.
     *
     * @return string
     */
    protected function getPluginInput()
    {
        return $this->argument('plugin');
    }

    /**
     * Get the source file path.
     *
     * @return string
     */
    protected function getSourcePath()
    {
        $className = get_class($this);
        $class = new \ReflectionClass($className);

        return dirname($class->getFileName());
    }

    public function copyStoreFile()
    {

    }


    protected FUNCTION getModelsConfig() {
        return  [
            'waka_woorder_document' => [
                'class' => 'Waka\Worder\Models\Document',
                'files' => [
                    ['attribute' => 'path','mode' => 'copyStore'],
                ]
            ],
            'waka_mailer_wakaMail' => [
                'class' => 'Waka\Mailer\Models\WakaMail',
            ],
            'waka_mailer_bloc' => [
                'class' => 'Waka\Mailer\Models\Bloc',
                'all' => true,
            ],
            'waka_mailer_layout' => [
                'class' => 'Waka\Mailer\Models\Layout',
                'all' => true,
            ],
        ];

    }

    protected function processVars($vars)
    {

        $cases = ['upper', 'lower', 'snake', 'studly', 'camel', 'title'];
        $modifiers = ['plural', 'singular', 'title'];

        foreach ($vars as $key => $var) {
            if (!is_array($var) && $var) {
                /*
                 * Apply cases, and cases with modifiers
                 */
                foreach ($cases as $case) {
                    $primaryKey = $case . '_' . $key;
                    $vars[$primaryKey] = $this->modifyString($case, $var);

                    foreach ($modifiers as $modifier) {
                        $secondaryKey = $case . '_' . $modifier . '_' . $key;
                        $vars[$secondaryKey] = $this->modifyString([$modifier, $case], $var);
                    }
                }

                /*
                 * Apply modifiers
                 */
                foreach ($modifiers as $modifier) {
                    $primaryKey = $modifier . '_' . $key;
                    $vars[$primaryKey] = $this->modifyString($modifier, $var);
                }
            } else {
                $vars[$key] = $var;
            }
        }

        return $vars;
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
            ['version', InputArgument::OPTIONAL, 'la version à mettre sur le nom du fichier'],
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
            ['force', null, InputOption::VALUE_NONE, 'Ecraser les fichiers'],
        ];
    }
}
