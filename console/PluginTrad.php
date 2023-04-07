<?php namespace Waka\Utils\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Winter\Storm\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Twig;
use File;
use Brick\VarExporter\VarExporter;

class PluginTrad extends GeneratorCommand
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
    private $codeLang;
    private $apiLimit;


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:trad';

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
        $this->getModelLangPath();
        $this->info($this->type . 'created successfully.');

    }

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars():array
    {
        $this->p_pluginCode = $pluginCode = $this->argument('plugin');
        $parts = explode('.', $pluginCode);
        $this->p_plugin = $plugin = array_pop($parts);
        $this->p_author = $author = array_pop($parts);
        $this->codeLang = $this->argument('lang');

        $this->p_model = $model = $this->option('model');
        return [];
    }

    protected function getModelLangPath() {
        $pluginSelectedPath = plugins_path(strtolower($this->p_author) .'/'. strtolower ($this->p_plugin) .'/lang');
        $this->originalFolder = $pluginSelectedPath .'/fr';
        $this->destinationFolder = $pluginSelectedPath .'/'.$this->codeLang;
        //TEST SI DOSSIER DESTTINATION EXISTE
        File::isDirectory($this->destinationFolder) or File::makeDirectory($this->destinationFolder, 0775);
        //
        $this->apiLimit = 0;
        $files = File::allfiles($this->originalFolder);
        foreach($files as $file) {
            $this->launchTradFile($file);
        }
    }

    /**
     * 
     */
    public function launchTradFile($file) {
        //trace_log($file);
        $fileName = $file->getRelativePathname();
        $fileLangName = $this->destinationFolder.'/'.$fileName;
        //trace_log($fileLangName);
        $content = null;
        $tradContent = null;
        $fileExiste = false;
        if(File::exists($fileLangName)) {
            $fileExiste = true;
            $content = include $file;
            $tradContent = include $fileLangName;
            $content = $this->array_diff_key_recursive($content, $tradContent);
            //trace_log($content);
        } else {
            $content = new Collection(include $file);
        }
        if(!$content) {
            /**/trace_log("pas de contenu");
            return;
        }
        $traduction = $this->recursiveLaunchTrad($content);

        if($fileExiste) {
            $traduction  = array_merge_recursive($tradContent, $traduction);
        }
    
        //FICHIER TRADUCTION PRET CREATION DU FICHIER DE LANGUE
        $traduction =  VarExporter::export($traduction,VarExporter::NO_CLOSURES);
        $stubLang = $this->getSourcePath() . '/trad/langVar.stub';
        $langStubContent = File::get($stubLang);
        $destinationContent = Twig::parse($langStubContent, ['data' => $traduction]);
        File::put($fileLangName,$destinationContent);
    }
    /**
     * storeMapKey
     * enregistre les clefs du tableau dans les valeurs d'un tableau
     * si il y a un sous tableau la ligne 0 a le nom de la clef
     */

    public function recursiveLaunchTrad($rows) {
        
        $newMap  = [];
        foreach($rows as $key=> $row) {
            if(is_array($row)) {
                $row =  $this->recursiveLaunchTrad($row);
                $newMap[$key] =  $row;
            } else {
                $this->apiLimit++;
                if($this->apiLimit > 99) {
                    sleep(1);
                    $this->apiLimit = 0;
                }
                trace_log($row);
                trace_log($this->codeLang.'---------------------------------------');
                try {
                    $translation = \GoogleTranslate::translate($row, 'fr',$this->codeLang);
                    trace_log($translation);
                    $rowTraducted = $translation['translated_text'] ?? null;
                    $newMap[$key] =  $rowTraducted;
                } catch (\Exception $ex) {
                    trace_log($ex->getMessage());
                    trace_log('error');
                    $newMap[$key] =  $row;
                }
                
                
                
            }
        }
        return $newMap;
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
            ['lang', InputArgument::REQUIRED, 'lang destination'],
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
