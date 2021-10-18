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
    public $pluginCode;
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
        $this->pluginCode = $this->argument('plugin');

        $parts = explode('.', $this->pluginCode);
        $plugin = array_pop($parts);
        $author = array_pop($parts);
        $this->model = $this->argument('model');
        $this->conserveId = $this->option('id');
        //
        $classes = $this->getModelsConfig();
        $classes = array_keys($classes);
        $this->classes = $this->choice('Class A absorber', $classes, 0, null, true);
        //
        return [
            'author' => $author,
            'plugin' => $plugin,
            'model' => $this->model,
            'version' => $this->argument('version'),
            'conserveId' => $this->option('id'),
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
        $seedFileName = null;
        $seedClassName = null;
        $classOptions = $class['options'] ?? [];
        $classProd = $class['options']['prod'] ?? false;
        if($classProd ?? false) {
            //les classes prods sont les classes de production on va adjoindre le nom de la classe producteur pour s'y retrouver.
            $seedFileName = $this->vars['lower_model'].'_'.$classIndex.$this->vars['version'];
            $seedClassName = $this->vars['studly_model'].ucfirst(camel_case($classIndex)).$this->vars['version'];
        } else {
            $seedFileName = $this->vars['lower_model'].'_'.$this->vars['version'];
            $seedClassName = $this->vars['studly_model'].$this->vars['version'];
        }
        $destinationFile = $this->getDestinationPath() . '/updates/seeds/seed_' . $seedFileName .'.php';
        //
        $className = $class['class'];
        $files = $class['files'] ?? [];
        $fileUploads = [];
        foreach($files as $file) {
            if(in_array($file['mode'], ['copyUpload'])) {
                array_push($fileUploads, $file['attribute']);
            }
        }
        $dataSourceName = $this->vars['lower_model'];
        $getAllDatas = $class['all'] ?? false;
        $datas = [];
        //Recherche des modèles existants : 
        if($class['options']['datasource'] ?? false) {
            $datas = $className::where('data_source', $dataSourceName);
        } else {
             $datas = $className::with($fileUploads);
        }
        if($withRules = $class['options']['with'] ?? false) {
            $withImploded = implode(",",$withRules);
            //trace_log($withImploded);
            $datas = $datas->with($withRules);
        }

        //
        $datas = $datas->get();
        //Remove ID for each row
        // $datas->transform(function ($item, $key) use ($files) {
        //     unset($item['id']);
        //     return $item;
        // });
        //$datas = $datas->toArray();
        //Création des fichiers
        $finalDatas = [];
        foreach($datas as $key=>$data) {
            //trace_log($data->toArray());
            $inject = [];
            $subDatas = [];
            $subDatas = $data->toArray();
            if($withRules) {
                foreach($withRules as $key=>$rule) {
                    $rules = $subDatas[$rule] ?? []; 
                    foreach($rules as $keyRow=>$rowRule) {
                        //$rowRule = $this->cleanAskFncRow($rowRule);
                        unset($rowRule['id']);
                        $inject['rules'][$rule][$keyRow] = $rowRule;
                        $inject['rules'][$rule][$keyRow]['data_to_string'] = VarExporter::export($rowRule,VarExporter::NO_CLOSURES,3);
                    }
                    unset($subDatas[$rule]);
                }
            }
            $inject['id'] = $subDatas['id'];
            unset($subDatas['id']);
            $inject['w_fileconfig'] = [];
            foreach($files as $filekey=>$file) {
                // On parocur la liste des fichier a copier
                $attributeName = $file['attribute'];
                //trace_log( "attribute Name ".$attributeName);
                //if($data[$attributeName] ?? false || $data->{$attributeName}) {
                    //Si on en trouve 1 on copie le fichier.
                    if($file['mode'] =='copyStore') {
                        $path = $data->{$attributeName};
                        $pathinfo = pathinfo($path);
                        $file['dirname'] = $pathinfo['dirname'] ?? '/';
                        $file['name'] = $pathinfo['basename'];
                        $file['srcPath'] = '/updates/seeds/files/'.$file['name'];
                        $path = storage_path('app/media'.$path);
                        $file['originalPath'] = $path;
                        
                    } else if ($file['mode'] =='copyUpload' && $data->{$attributeName}) {
                        $path = $data->{$attributeName}->getLocalPath();
                        //trace_log($path);
                        $file['originalPath'] = $path;
                        $file['name'] = $data->{$attributeName}->getFilename();
                        $file['srcPath'] = '/updates/seeds/files/'.$file['name'];
                    }
                    //On ajoute les information du fichier dans le row de données. 
                   
                    //On continue la création.
                    if($file['originalPath'] ?? false) {
                        $filePath = $this->getDestinationPath() . '/updates/seeds/files/'.$file['name'];
                        $this->makeDirectory($filePath);
                        $fileContent = $this->files->get($file['originalPath']);
                        $this->files->put($filePath, $fileContent);

                    }
                   
                    //
                    //$item['files'][$attributeName] = $file;
                    //
                //}
                
                array_push($inject['w_fileconfig'],$file);

            }
            foreach($subDatas as $key=>$subData) {
                // if(is_array($subData)) {
                //     $subDatas[$key] = json_encode($subData);
                // }
                if(in_array($key, $fileUploads)) {
                    unset($subDatas[$key]);
                }
            }
            //trace_log($inject);
            $inject['w_dataString'] = VarExporter::export($subDatas,VarExporter::NO_CLOSURES,3);
            array_push($finalDatas, $inject);  
        }
        $destinationContent = $this->files->get($sourceFile);
        $seedVars = array_merge($this->vars, ['datas' => $finalDatas ], ['className' => $className], ['seedClassName' => $seedClassName], ['classOptions' => $classOptions]);
        //trace_log($seedVars);
        $destinationContent = Twig::parse($destinationContent, $seedVars);
        $this->files->put($destinationFile, $destinationContent);

    }

    public function cleanAskFncRow($rule) {
        $toKeep = [
            'fnceable_id',
            'fnceable_type',
            'askeable_id',
            'askeable_type',
            'class_name',
            'data_source',
            'config_data',
            'created_at',
            'updated_at'
        ];
        foreach($rule as $key=>$column) {
            if(!in_array($key, $toKeep)) {
                unset($rule[$key]);
            }
        }
        return $rule;

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
        $classes =   [
            'waka_importExport_export' => [
                'class' => 'Waka\ImportExport\Models\Export',
                'options' => [
                    'prod' => true,
                    'datasource' => true,
                    'requiredBehavior' => 'Waka.'
                ]
            ],
            'waka_importExport_import' => [
                'class' => 'Waka\ImportExport\Models\Import',
                'options' => [
                    'datasource' => true,
                    'prod' => true,
                ]
            ],
            'waka_worder_document' => [
                'class' => 'Waka\Worder\Models\Document',
                'files' => [
                    ['attribute' => 'path','mode' => 'copyStore'],
                ],
                'options' => [
                    'datasource' => true,
                    'prod' => true,
                    'with' => [
                        'rule_asks',
                        'rule_fncs',
                    ]
                ]
            ],
            'waka_mailer_wakaMail' => [
                'class' => 'Waka\Mailer\Models\WakaMail',
                'options' => [
                    'datasource' => true,
                    'prod' => true,
                    'with' => [
                        'rule_asks',
                        'rule_fncs',
                    ]
                ]
            ],
            'waka_mailer_bloc' => [
                'class' => 'Waka\Mailer\Models\Bloc',
                'options' => [
                    'prod' => true,
                ]
            ],
            'waka_mailer_layout' => [
                'class' => 'Waka\Mailer\Models\Layout',
                'options' => [
                    'prod' => true,
                ]
            ],
            'waka_pdfer_wakaPdf' => [
                'class' => 'Waka\Pdfer\Models\WakaPdf',
                'options' => [
                    'datasource' => true,
                    'prod' => true,
                    'with' => [
                        'rule_asks',
                        'rule_fncs',
                    ]
                ]
            ],
            'waka_pdfer_bloc' => [
                'class' => 'Waka\Pdfer\Models\Bloc',
                'options' => [
                    'prod' => true,
                ]
            ],
            'waka_pdfer_layout' => [
                'class' => 'Waka\Pdfer\Models\Layout',
                'options' => [
                    'prod' => true,
                ]
            ],
            'waka_segator_layout' => [
                'class' => 'Waka\Segator\Models\Tag',
                'options' => [
                    'prod' => true,
                ]
            ],
        ];
        $lowerModel = strtolower($this->model);
        $modelClasses = \Config::get($this->pluginCode.'::seeds.'. $lowerModel);
        if($modelClasses) {
            return array_merge([$lowerModel => $modelClasses ], $classes);
        } else {
            return $classes;
        }

        
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
            ['id', null, InputOption::VALUE_NONE, 'Ajouter les id'],
        ];
    }
}
