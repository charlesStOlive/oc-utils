<?php namespace Waka\Utils\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Winter\Storm\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Twig;
use File;
use Brick\VarExporter\VarExporter;

class ExcelTrad extends GeneratorCommand
{
    public $wk_pluginCode;
    public $Wk_name;
    public $wk_plugin;
    public $wk_author;
    public $wk_model;
    public $remover;
    //
    private $originalFolder;
    private $destinationFolder;
    private $arrayLangs;
    private $apiLimit;


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:excelTrad';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lancer la traduction de langues';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'lang';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */

    protected $stubs = [];

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $this->vars = $this->prepareVars();
        $mode = $this->choice('Import ou export', ['lang vers excel', 'excel vers lang'], 0, null, true);
        //trace_log($mode);
        if($mode[0] == 'lang vers excel') {
            $this->createExcel();
            $this->info($this->type . 'Excel crée avec succès');

        } else {
            $this->importExcel();
        }
        
    }

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {
        $this->p_pluginCode = $pluginCode = $this->argument('plugin');
        $parts = explode('.', $pluginCode);
        $this->p_plugin = $plugin = array_pop($parts);
        $this->p_author = $author = array_pop($parts);
        $codeLangs = $this->argument('langs');
        $this->arrayLangs = explode(',', $codeLangs);
        $this->p_model = $model = $this->option('model');
    }

    public function getExcelFileName() {
        return 'temp/'.$this->p_plugin.'_'.$this->p_author.'_lang.xlsx';
    }

    protected function createExcel() {
        $allLanfFiles = [];
        //On recherche le chemin de lang des fichiers.
        $pluginSelectedPath = plugins_path(strtolower($this->p_author) .'/'. strtolower ($this->p_plugin) .'/lang');
        //On recherche la langue de base ici le fr. 
        $originalFolder = $pluginSelectedPath .'\\fr';
        $originalFiles = File::allfiles($originalFolder);
        //
        foreach($originalFiles as $file) {
            $content = include $file;
            $fileName = $file->getBasename();
            $fileName = explode('.', $fileName);
            $fileWithoutExtention = array_shift($fileName);
            $content = array_dot($content);
            $allLanfFiles[$fileWithoutExtention]['fr'] = $content;
        }
        //$allLanfFiles['fr'] = $contentOriginFiles;
        //
        foreach($this->arrayLangs as $lang) {
            $destinationFolder = $pluginSelectedPath .'\\'.$lang;
            //trace_log($destinationFolder);
            $directoryExiste = File::isDirectory($destinationFolder);
            $contentFiles = [];
            if($directoryExiste) {
                $tradFiles = File::allfiles($destinationFolder);
                foreach($tradFiles as $file) {
                    $content = include $file;
                    $fileName = $file->getBasename();
                    $fileName = explode('.', $fileName);
                    $fileWithoutExtention = array_shift($fileName);
                    $content = array_dot($content);
                    $allLanfFiles[$fileWithoutExtention][$lang] = $content;
                }
            }
        }
        \Excel::store(new \Waka\Utils\Classes\Exports\ExportAll($allLanfFiles, $this->arrayLangs ), $this->getExcelFileName());
    }

    protected function importExcel() {
        $allLanfFiles = [];
        //On recherche le chemin de lang des fichiers.
        $pluginSelectedPath = plugins_path(strtolower($this->p_author) .'/'. strtolower ($this->p_plugin) .'/lang');
        $originalFolder = $pluginSelectedPath .'\\fr';
        $originalFiles = File::allfiles($originalFolder);
        //
        $langFiles = [];
        //
        foreach($originalFiles as $file) {
            $fileName = $file->getBasename();
            $fileName = explode('.', $fileName);
            $fileWithoutExtention = array_shift($fileName);
            array_push($langFiles, $fileWithoutExtention);
        }
        $path = storage_path('app/'.$this->getExcelFileName());

        $importExcel = new \Waka\Utils\Classes\Imports\ImportLang($langFiles, $this->arrayLangs);
        \Excel::import($importExcel, $path);
        //Travail sur la langue source

        //
        $pluginSelectedPath = plugins_path(strtolower($this->p_author) .'/'. strtolower ($this->p_plugin) .'/lang');
        //Ajout du fr comme premiere langue
        array_unshift($this->arrayLangs , 'fr');

        //trace_log($importExcel);


        foreach($this->arrayLangs as $lang) {
            $folderLangPath = $pluginSelectedPath.'/'.$lang;
            File::isDirectory($folderLangPath) or File::makeDirectory($folderLangPath, 0775);
            foreach($importExcel->sheetArray as $key=>$file) {
                $sheetName = $key;
                $data = $file->data[$lang] ?? null;
                $finalData = [];
                if($data) {
                    foreach ($data as $key => $value) {
                        array_set($finalData, $key, $value);
                    }
                }
                $filePath = $folderLangPath.'/'.$sheetName.'.php';
                //trace_log($filePath);
                //trace_log($finalData);

                //Methode 1
                // $stubLang = $this->getSourcePath() . '/trad/lang.stub';
                // $langStubContent = File::get($stubLang);
                // $destinationContent = Twig::parse($langStubContent, ['data' => $finalData]);

                //Methode 2
                $finalData =  VarExporter::export($finalData,VarExporter::NO_CLOSURES);
                $stubLang = $this->getSourcePath() . '/trad/langVar.stub';
                $langStubContent = File::get($stubLang);
                $destinationContent = Twig::parse($langStubContent, ['data' => $finalData]);


                File::put($filePath,$destinationContent);
            }
        }
    }

    function array_diff_key_recursive (array $arr1, array $arr2) {
        $diff = array_diff_key($arr1, $arr2);
        $intersect = array_intersect_key($arr1, $arr2);
        foreach ($intersect as $k => $v) {
            if (is_array($arr1[$k]) && is_array($arr2[$k])) {
                $d = $this->array_diff_key_recursive($arr1[$k], $arr2[$k]);
                if ($d) {
                    $diff[$k] = $d;
                }
            }
        }
        return $diff;
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
            ['langs', InputArgument::REQUIRED, 'lang destination'],
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
            ['model', null, InputOption::VALUE_NONE, 'The name of the model. Eg: Post'],
        ];
    }
}
