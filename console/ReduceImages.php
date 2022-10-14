<?php namespace Waka\Utils\Console;

use Lang;
use File;
use Config;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use System\Classes\PluginManager;
use System\Models\Parameter;
use System\Models\File as FileModel;
use Spatie\Image\Image as SpatieImage;
use Winter\Storm\Database\Attach\Resizer;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class ReduceImages extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:reduceImages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permet de nettoyer les fichiers d\'upload';

    protected $executeReduction = false;

    protected $cleanFile = false;


    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if($this->option('exec')) {
            $this->executeReduction = true;
        }
        $this->reduceFiles();
    }

    public function reduceFiles() {
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerImagesToReduce');
        $modelsToReduces = [];
        if($this->option('exec')) {
            $this->executeReduction = true;
        }
        foreach ($bundles as $bundle) {
            $bundleSoftDelete = $bundle['cleanSoftDelete'] ?? [];
            $modelsToReduces = array_merge($bundle, $modelsToReduces);
        }
        foreach($modelsToReduces as $className=>$properties) {
            foreach($properties as $field=>$opt) {
                $configMaxSize = $opt['maxSize'] ?? 1000;
                $maxSize = $configMaxSize * 1024;
                $configPxSize = $opt['largestPx'] ?? 1920;
                $allImages = \System\Models\File::where('attachment_type', $className)->where('field', $field)->where('file_size' ,'>', $maxSize-100)->get();
                //trace_log(sprintf('Image trouvé pour la classe %s et le champs %s : %s',$className, $field,  $allImages->count()));
                if($allImages->count()) {
                    foreach($allImages as $image) {
                        $this->reduceImage($image, $configPxSize);
                    }
                        
                }
            }
            
            
        }
        //

    }

    private function reduceImage($image, $configPxSize) {
        
        if($image->isImage()) {
            $path = $image->getLocalPath();
            
            $width = $image->width;
            $height = $image->height;
            $largest = null;
            if($width > $height) {
                $largest = $width;
            } else {
                $largest = $height;
            }
            $max = $configPxSize;
            $ratioReduction = $max / $largest;
            //trace_log(sprintf('Id : %s ration de reduction : %s, ancienne taille : %s , nouvelle taille %s', $image->id, $ratioReduction, $image->file_size, $image->file_size*$ratioReduction));
            if($this->executeReduction) {
                //trace_log('execution');
                //TODO je n'arrive pas a recuperer la taille de la nouvelle image si je garde le même non
                // Sans doute un problème de cache
                // je crée une image (_n)), je calcul la taille puis je remplace sopn nom par l'original.
                $pathInfo = pathinfo($path);
                $newFilePath = $pathInfo['dirname'].'/'. $pathInfo['filename'].'_n.'.$pathInfo['extension'];
                $realPath = $pathInfo['dirname'].'/'. $pathInfo['filename'].'.'.$pathInfo['extension'];
                // trace_log($newFilePath);
                // trace_log($realPath);
                $optimizedImage  = Resizer::open($path);
                $optimizedImage->resize($width * $ratioReduction, $height * $ratioReduction, [])->save($newFilePath);
                $fileSize = File::size($newFilePath);
                $image->file_size = $fileSize;
                //Maintenant que nous avons la bonne taille on renome le fichier
                rename($newFilePath, $realPath);
                //ici je sauvegarde l'objet image ( pour la taille)
                $image->save();
            } else {
                $optimizedImage = null;
            }
        }
    }

    protected function utilPurgeUploads()
    {
        $daysMonth = \Carbon\Carbon::now()->subDays(31);
        $daysHour = \Carbon\Carbon::now()->subHour(1);
        $daysWeek = \Carbon\Carbon::now()->subDays(7);

        $uploadsDisk = Config::get('cms.storage.uploads.disk', 'local');
        if ($uploadsDisk !== 'local') {
            $this->error("Purging uploads is only supported on the 'local' disk, current uploads disk is $uploadsDisk");
            return;
        }

        $totalCount = 0;
        $validFiles = FileModel::pluck('disk_name')->all();
        $uploadsPath = Config::get('filesystems.disks.local.root', storage_path('app')) . '/' . Config::get('cms.storage.uploads.folder', 'uploads');

        // Recursive function to scan the directory for files and ensure they exist in system_files.
        $purgeFunc = function ($targetDir) use (&$purgeFunc, &$totalCount, $uploadsPath, $validFiles, $daysHour,$daysWeek, $daysMonth ) {
            if ($files = File::glob($targetDir.'/*')) {
                if ($dirs = File::directories($targetDir)) {
                    foreach ($dirs as $dir) {
                        $purgeFunc($dir);

                        if (File::isDirectoryEmpty($dir) && is_writeable($dir)) {
                            rmdir($dir);
                            $this->info('Removed folder: '. str_replace($uploadsPath, '', $dir));
                        }
                    }
                }

                foreach ($files as $file) {
                    if (!is_file($file)) {
                        continue;
                    }

                    // Skip .gitignore files
                    if ($file === '.gitignore') {
                        continue;
                    }

                    $fileEndPath = str_replace($uploadsPath, '', $file);
                    $inWeek = str_contains($fileEndPath, 'week');
                    $inMonth = str_contains($fileEndPath,'month');
                    $isThumb = str_contains($fileEndPath,'thumb');

                    // Skip files unable to be purged
                    if (!is_writeable($file)) {
                        $this->warn('Unable to purge file: ' . $fileEndPath);
                        continue;
                    }

                    // Skip valid files
                    if (in_array(basename($file), $validFiles)) {
                        $this->warn('Skipped file in use: '. $fileEndPath);
                        continue;
                    }
                    $date = \Carbon\Carbon::parse(filemtime($file));


                    
                    if($daysHour->lte($date)) {
                        $this->warn('Skipped file to young: '. $fileEndPath);
                        continue;
                    }
                    $this->warn($date." Date fichier : ".$date->isoFormat('LLL')." Date 1h : ".$daysHour->isoFormat('LLL')."  inWeek:".$inWeek." inMonth: ".$inMonth." isThumb: ".$isThumb);
                    if($daysWeek->lte($date) && $inWeek) {
                        $this->warn('Skipped file 1h-1w but in month or in week or isThumb:  '. $fileEndPath);
                        continue;
                    }
                    //$this->warn($date." Date fichier : ".$date->isoFormat('LLL')." Date 1h : ".$daysHour->isoFormat('LLL')."  inWeek:".$inWeek." inMonth: ".$inMonth." isThumb: ".$isThumb." date sup oneweek ".$date->gte($daysWeek));
                    if($daysMonth->lte($date) && ($inMonth or $isThumb)) {
                        $this->warn('Skipped file 1w-1m and in month or isThumb: '. $fileEndPath);
                        continue;
                    }
                    
                    
                    if($this->executeReduction) {
                        unlink($file);
                        $this->info('Purged: '. $fileEndPath);
                    } else {
                        $this->info('Will be Purged: '. $fileEndPath);
                    }
                    $totalCount++;
                }
            }
        };

        $purgeFunc($uploadsPath);

        if ($totalCount > 0 && $this->executeReduction) {
            $this->comment(sprintf('Successfully deleted %d invalid file(s), leaving %d valid files', $totalCount, count($validFiles)));
        } elseif($totalCount > 0 && !$this->executeReduction) {
             $this->comment(sprintf('if executed it will  delete %d invalid file(s), leaving %d valid files', $totalCount, count($validFiles)));
        } else {
            $this->comment('No files found to purge.');
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['exec', null, InputOption::VALUE_NONE, 'Executer le clean sinon affiche en log uniquement'],
        ];
    }
}
