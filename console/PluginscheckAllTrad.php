<?php

namespace Waka\Utils\Console;

use System\Console\BaseScaffoldCommand;

class PluginscheckAllTrad extends BaseScaffoldCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected static $defaultName = 'waka:checktrads';

    /**
     * @var string The name and signature of this command.
     */
    protected $signature = 'waka:checktrads
        {--s|simulate : Simuler ne pas créer les fichiers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Met a jour les contenus de lang en fonction du contenu des fichiers.';

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
        $directories = ['wcli', 'waka', '../themes'];

        $analysed = $this->getAnalysedFiles($directories);
        $existing = $this->extractLanguageFiles($directories);
        //trace_log($analysed['nested']);
        // trace_log($existing);
        $result = $this->mergeAndFindDifferenceRecursive($analysed['nested'], $existing);
        $this->createLanguageFiles($result['mergedArray']);
    }

    private function mergeAndFindDifferenceRecursive(array $array1, array $array2)
    {
        $mergedArray = [];
        $onlyInArray1 = [];
        $onlyInArray2 = [];

        foreach ($array1 as $key => $value) {
            if (array_key_exists($key, $array2)) {
                if (is_array($value) && is_array($array2[$key])) {
                    $result = $this->mergeAndFindDifferenceRecursive($value, $array2[$key]);
                    $mergedArray[$key] = $result['mergedArray'];
                    $onlyInArray1[$key] = $result['onlyInArray1'];
                    $onlyInArray2[$key] = $result['onlyInArray2'];
                } else {
                    $mergedArray[$key] = $array2[$key];
                }
            } else {
                $mergedArray[$key] = $value;
                $onlyInArray1[$key] = $value;
            }
        }

        foreach ($array2 as $key => $value) {
            if (!array_key_exists($key, $array1)) {
                $onlyInArray2[$key] = $value;
            }
        }

        // $this->cleanArray($onlyInArray1);
        // $this->cleanArray($onlyInArray2);


        return [
            'mergedArray' => $mergedArray,
            'onlyInArray1' => $onlyInArray1,
            'onlyInArray2' => $onlyInArray2,
        ];
    }

    function createLanguageFiles(array $langArray)
    {
        $basePath = base_path('plugins');

        foreach ($langArray as $vendorAndPlugin => $langFiles) {
            list($vendor, $plugin) = explode('.', $vendorAndPlugin);
            $langPath = $basePath . '/' . $vendor . '/' . $plugin . '/lang/fr';

            // Crée le dossier lang/fr s'il n'existe pas
            if (!is_dir($langPath)) {
                mkdir($langPath, 0755, true);
            }

            foreach ($langFiles as $langKey => $langContent) {
                $this->recursive_ksort($langContent);
                $langFile = $langPath . '/' . $langKey . '.php';

                // Génère le contenu du fichier PHP avec la syntaxe moderne
                $fileContent = '<?php' . PHP_EOL . PHP_EOL;
                $fileContent .= 'return ' . \Brick\VarExporter\VarExporter::export($langContent) . ';' . PHP_EOL;

                // Crée le fichier de langue
                if (!$this->option('simulate')) {
                    if (!file_exists($langFile)) {
                        $this->info('missing lang file '.$langFile);
                        $this->info(json_encode($langContent, true));
                        if ($this->confirm('Vous allez créer un nouveau fichier ici : '.$langFile)) {
                            file_put_contents($langFile, $fileContent);
                        }
                    } else {
                        file_put_contents($langFile, $fileContent);
                    }
                    
                }
            }
        }
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

    private function cleanArray(array &$array, int $level = 0)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->cleanArray($value, $level + 1);
                if (empty($value) && $level >= 2) {
                    unset($array[$key]);
                }
            } elseif ($value === null && $level >= 2) {
                unset($array[$key]);
            }
        }
    }



    private function extractLanguageFiles(array $vendors)
    {
        $basePath = base_path('plugins');
        $langArray = [];

        foreach ($vendors as $vendor) {
            $vendorPath = $basePath . '/' . $vendor;

            // Vérifie si le dossier du vendor existe
            if (is_dir($vendorPath)) {
                // Récupère la liste des dossiers de plugins
                $pluginFolders = glob($vendorPath . '/*', GLOB_ONLYDIR);

                foreach ($pluginFolders as $pluginFolder) {
                    $pluginName = basename($pluginFolder);
                    $langPath = $pluginFolder . '/lang/fr';

                    // Vérifie si le dossier lang/fr existe pour ce plugin
                    if (is_dir($langPath)) {
                        $langFiles = glob($langPath . '/*.php');

                        foreach ($langFiles as $langFile) {
                            $langKey = basename($langFile, '.php');
                            $langContent = include $langFile;
                            $langArray[$vendor . '.' . $pluginName][$langKey] = $langContent;
                        }
                    }
                }
            }
        }

        return $langArray;
    }


    private function getAnalysedFiles($directories)
    {
        $strings = collect();
        $pattern = '/(?P<src>\w+\.\w+)::(?P<full_key>(?P<lg>\w+)(\.(?P<mid_key>\w+(\.\w+)*))?\.(?P<key>\w+))/';
        foreach ($directories as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(plugins_path($directory)));
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), ['yaml', 'php', 'htm', 'md'])) {
                    $content = file_get_contents($file);
                    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER,0);
                    //trace_log($matches);
                    foreach ($matches as $i=>$match) {
                        if(!$src = $match['src'] ?? false) {
                            continue;
                        }
                        if(!$lg = $match['lg'] ?? false) {
                            continue;
                        }
                        if(!$mid_key = $match['mid_key'] ?? false) {
                            $mid_key = $match['key'];
                        }
                        $strings->push([
                            'vp' => $src,
                            'file' => $lg,
                            'code' => $mid_key,
                            'key' => $match['key'],
                            'full' => $match[0] ?? null,
                        ]);
                    }
                } else {
                    //trace_log('refusé : '.trace_log($file->getFilename()));
                }
            }
        }
        
        
        trace_log('count strings : '.$strings->count());
        $strings =  $strings->filter(function ($fileGroup, $vpKey) {
            $lang = \Lang::get($fileGroup['full']);
            if($lang == $fileGroup['full']) {
                // trace_log('Pas de langue pour '.$fileGroup['full']);
                //La trad n'existe pas on check si c'est un settings. 
                if(\Config::get($fileGroup['full'])) {
                    //trace_log('une config ! '.$fileGroup['full']);
                    return false;
                } 
            } 
            return true;
        });
        trace_log('count collection after : '.$strings->count());
        $Collection = $strings->sortby('code')->sortby('file')->sortby('vp')->groupBy(['vp', 'file']);
        
        // $flattFileVpCollection = $Collection->map(function ($fileGroup, $vpKey) {
        //     return $fileGroup->map(function ($codeGroup, $fileKey) {
        //         $combined = [];
        //         foreach ($codeGroup as $item) {
        //             $combined[$item['code']] = null;
        //         }

        //         return $combined;
        //     });
        // });
        
        $nestedCollection = $Collection->map(function ($fileGroup, $vpKey) {
            // trace_log('--file group--');
            // trace_log($fileGroup);
            return $fileGroup->map(function ($codeGroup, $fileKey) {
                $combined = [];
                foreach ($codeGroup as $item) {
                    // trace_log($item);
                    $code = $item['code'].'.'.$item['key'];
                    // trace_log($code);
                    array_set($combined, $code, null);
                }
                array_walk_recursive($combined, function (&$value, $key) {
                    if (is_array($value)) {
                        ksort($value);
                    }
                });

                return $combined;
            });
        });
        // trace_log($nestedCollection->toArray());
        return [
            'nested' => $nestedCollection->toArray(),
            // 'flatten' => $flattFileVpCollection->toArray(),
        ];
    }

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars(): array
    {
        return [];

        // $this->p_model = $model = $this->option('model');
    }
}
