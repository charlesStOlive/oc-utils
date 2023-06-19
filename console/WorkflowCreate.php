<?php

namespace Waka\Utils\Console;

use System\Console\BaseScaffoldCommand;
use Winter\Storm\Support\Collection;
use Twig;

class WorkflowCreate extends BaseScaffoldCommand
{
    public $wk_pluginCode;
    public $Wk_name;
    public $wk_plugin;
    public $wk_author;
    public $wk_model;
    public $remover;

    protected static $defaultName = 'waka:workflow';

    /**
     * @var string The name and signature of this command.
     */
    protected $signature = 'waka:workflow
        {name : The name of the workflow. <info>(eg: Winter.Blog)</info>}
        {plugin : The name of the plugin. <info>(eg: Winter.Blog)</info>}
        {model : The name of the command to generate. <info>(eg: ImportPosts)</info>}
        {src : nom de la source excel. <info>(eg: start_wcms.xlsx)</info>}
        {--l|listener : Créer le listener}
        {--f|force : Overwrite existing files  with generated files.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new workflow';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Yaml';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */

    protected $stubs = [
        'workflow/workflow.stub' => 'config/{{name}}.yaml',
        'workflow/temp_lang.stub' => 'lang/fr/{{name}}.php',
        'workflow/listener.stub' => 'listeners/Workflow{{name | studly }}Listener.php',
        'workflow/description.stub' => 'docs/wf_{{name}}.md',
    ];

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        //trace_log('je commence worrkflow create');
        $this->vars = $this->prepareVars(true);
        //
        $this->call('cache:forget', ['key' => 'allWorkflows']);

        //trace_log($this->vars);

        $this->makeStubs();
        //Je fait les stubs la langue est géré ailleurs.
        $this->makeLangFiles();

        $this->controleWorkflow();


        //trace_log('je fait les stubs');

        $this->info($this->type . 'created successfully.');
        //trace_log('je calll workflowDump');
        $this->call('waka:workflowDump', [
            'workflowName' => $this->wk_name,
            'plugin' => $this->wk_pluginCode,
            'model' => $this->wk_model,
        ]);
    }

    public function makeStub($stubName)
    {
        if (!isset($this->stubs[$stubName])) {
            return;
        }

        $sourceFile = $this->getSourcePath() . '/' . $stubName;
        $destinationFile = $this->getDestinationPath() . '/' . $this->stubs[$stubName];
        $destinationContent = $this->files->get($sourceFile);
        //
        $destinationContent = Twig::parse($destinationContent, $this->vars);
        $destinationFile = Twig::parse($destinationFile, $this->vars);
        //
        $this->makeDirectory($destinationFile);
        $this->files->put($destinationFile, $destinationContent);
    }
    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars($putTrans = false): array
    {
        $this->wk_name = $name = $this->argument('name');

        $this->wk_pluginCode = $pluginCode = $this->argument('plugin');
        $parts = explode('.', $pluginCode);
        $this->wk_plugin = $plugin = array_pop($parts);
        $this->wk_author = $author = array_pop($parts);

        $this->wk_model = $model = $this->argument('model');

        $fileName = 'start';

        if ($this->argument('src')) {
            $fileName = $this->argument('src');
        }
        $startPath = null;
        if ($this->wk_author == 'waka') {
            $startPath = env('SRC_WAKA');
        }
        if ($this->wk_author == 'wcli') {
            $startPath = env('SRC_WCLI');
        }

        $filePath =  $startPath . '/' . $fileName . '.xlsx';
        //trace_log($filePath);

        if (!$this->option('listener')) {
            unset($this->stubs['workflow/listener.stub']);
        }

        $importExcel = new \Waka\Utils\Classes\Imports\ImportWorkflow($name);
        //trace_log($filePath);
        \Excel::import($importExcel, $filePath);
        $places = new Collection($importExcel->places->data);
        $trans = new Collection($importExcel->trans->data);
        $config = new Collection($importExcel->config->data);

        $data = [
            'putTrans' => $putTrans, //Active la traduction ou pas des codes de place et de transition
            'pluginCode' => $this->wk_pluginCode,
            'plugin' => $this->wk_plugin,
            'author' => $this->wk_author,
            'model' => $this->wk_model,
            'places' => $places,
            'trans' => $trans,
            'config' => $config,
            'name' => $this->wk_name,
        ];




        $prepareExcel = new \Waka\Utils\Classes\CreateWorkflowDataFromExcel();



        //trace_log($prepareExcel->prepareVars($data));
        return $prepareExcel->prepareVars($data);
    }

    /**
     * Make a single stub.
     *
     * @param string $stubName The source filename for the stub.
     */
    public function makeOneStub($stubName, $destinationName, $tempVar)
    {

        $sourceFile = $this->getSourcePath() . '/' . $stubName;
        $destinationFile = $this->getDestinationPath() . '/' . $destinationName;
        $destinationContent = $this->files->get($sourceFile);
        /*
         * Parse each variable in to the destination content and path
         */
        $destinationContent = Twig::parse($destinationContent, $tempVar);
        $destinationFile = Twig::parse($destinationFile, $tempVar);

        $this->makeDirectory($destinationFile);

        /*
         * Make sure this file does not already exist
         */
        if ($this->files->exists($destinationFile) && !$this->option('force')) {
            throw new \Exception('Stop everything!!! This file already exists: ' . $destinationFile);
        }

        $this->files->put($destinationFile, $destinationContent);
    }

    protected function makeLangFiles()
    {

        $destinationFile = $this->getDestinationPath() . '/lang/fr/' . $this->vars['name'] . '.php';
        $places = array_merge($this->vars['tradPlaces'], ['comments' => $this->vars['tradPlacesCom']]);
        $trans = array_merge($this->vars['tradTrans'], ['comments' => $this->vars['tradTransCom']],  ['buttons' => $this->vars['tradButton']]);
        $scopesLang = $this->vars['scopes']->pluck('label', 'key')->toArray();
        $langContent = array_merge($this->vars['trads'], ['places' =>  $places], ['trans' =>  $trans], ['scopes' => $scopesLang] );
        $this->recursive_ksort($langContent);
        $fileContent = '<?php' . PHP_EOL . PHP_EOL;
        $fileContent .= 'return ' . \Brick\VarExporter\VarExporter::export($langContent) . ';' . PHP_EOL;
        file_put_contents($destinationFile, $fileContent);
    }

    protected function recursive_ksort(&$array)
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursive_ksort($value);
            }
        }
    }


    protected function controleWorkflow()
    {
        $this->info('** CONTROLE DES WORKFLOWS **');
        $this->info('-- ctrl des fonctions --');
        $usedFncs = [];
        foreach ($this->vars['trans'] as $trans) {
            // Parcours des fonctions contenues dans chaque élément de trans
            foreach ($trans['functions'] as $fncName=>$fonction) {
                //trace_log($fncName);
                //trace_log($fonction);
                array_push($usedFncs, $fncName);
                // Comparaison de la clé de fncs avec la valeur de fnc
                if (array_key_exists($fncName, $this->vars['fncs'])) {
                    // Faire quelque chose si la clé existe
                    
                } else {
                    // Faire quelque chose si la clé n'existe pas
                    $this->error('TRANS :  fonction ' . $fncName . ' n\'existe pas dans les fncs.');
                }
            }
        }
        $fncs = array_keys($this->vars['fncs']);
        //trace_log($fncs);
        $diffs = array_diff($fncs, $usedFncs);
        foreach($diffs as $diff) {
            $this->error('FNC : La fonction ' . $diff . ' n\'est pas utilisé');
        }
        $this->info('-- ctrl du listener --');
        $listenerClassPath = $this->getDestinationPath() . '/listeners/Workflow'.\Str::studly($this->vars['name']).'Listener.php';
        if(!file_exists($listenerClassPath)) {
            $this->error('Le fichier n\'existe pas ' .$listenerClassPath);
        }
        $classConstructionName = Twig::parse('\{{author | studly }}\{{plugin | studly}}\Listeners\Workflow{{name | studly}}Listener',$this->vars);
        $classToTest = new $classConstructionName;
        $this->info('-- ctrl du listener : '.$classConstructionName.' --');
        $existingMethods = get_class_methods($classToTest);
        $listenerFncUsed = ['subscribe','onGuard','onLeave','onTransition','recLogs', 'onEnter', 'onEntered', 'onAfterSavedFunction','launchFunction','launchGardFunction'  ];
        //trace_log("-------------existingMethods---------");
        //trace_log($existingMethods);
        foreach($fncs as $fnc) {
            array_push($listenerFncUsed, $fnc);
            if (!method_exists($classToTest, $fnc)) {
                $this->error('Le méthode  n\'existe pas ' .$fnc.' dans la classe '.$classConstructionName);
            } else {
                // $this->info('Le méthode  ' .$fnc.' est ok ');
                
                
            }
        }
        //trace_log($listenerFncUsed);
        $diffs = array_diff($existingMethods, $listenerFncUsed);
        foreach($diffs as $diff) {
            $this->error('Listener : La fonction ' . $diff . ' n\'est pas utilisé dans le workflow');
        }

    }
}
