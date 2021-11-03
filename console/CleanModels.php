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

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class CleanModels extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:cleanModels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permet de nettoyer les models enregistré dans la méthode registerModelToClean';

    protected $extecute = false;

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
        $bundles = PluginManager::instance()->getRegistrationMethodValues('registerModelToClean');
        $sofDeleteToClean = [];
        $modelToDelete = [];
        $modelToAnonymize = [];
        if($this->option('execute')) {
            $this->execute = true;
        }
        if($this->option('cleanFile')) {
            $this->cleanFile = true;
        }
        foreach ($bundles as $bundle) {
            $bundleSoftDelete = $bundle['cleanSoftDelete'] ?? [];
            $sofDeleteToClean = array_merge($sofDeleteToClean, $bundleSoftDelete);
            //
            $deleteBundle = $bundle['delete'] ?? [];
            $modelToDelete = array_merge($modelToDelete, $deleteBundle);

            $anonymizeBundle = $bundle['anonymize'] ?? [];
            $modelToAnonymize = array_merge($modelToAnonymize, $anonymizeBundle);
        }
        $this->cleanSoftDeleteModels($sofDeleteToClean);
        $this->deleteModels($modelToDelete);
        $this->anonymizeModels($modelToAnonymize);
        //On purge les modèles inutiles.
        if($this->cleanFile) {
            $this->utilPurgeUploads();
        }
        
    }

    public function cleanSoftDeleteModels($modelsToClean) {
        $today = \Carbon\Carbon::now();
        
        foreach($modelsToClean as $model=>$time) {
            $this->info('clean soft delete model : '.(string) $model);
            if($time == 0) $time = 7;
            $limitDate = $today->copy()->subDays($time);
            $query = $model::onlyTrashed()->where('deleted_at', '<', $limitDate);
            trace_log($query->get()->pluck('id')->toArray());
            $this->info('qty : '.$query->count());
            //$query->->forceDelete();

        }
    }

    public function deleteModels($modelsToDelete) {
        $today = \Carbon\Carbon::now();
        foreach($modelsToDelete as $model=>$data) {
            $this->info('delete model : '.(string) $model);
            $time = $data['nb_day'] ?? 7;
            $column = $data['column'] ?? 'updated_at';
            $scope = $data['scope'] ?? null;
            if($time == 0) $time = 7;
            $limitDate = $today->copy()->subDays($time);
            $model = $model::where($column, '<', $limitDate);
            if($scope) {
                $model = $model->$scope();
            }
            \Log::info($model->get()->pluck('id')->toArray());
            $this->info('qty : '.$model->count());
           //$this->table(['ids'], [$model->get()->pluck('id')->toArray()]);
            //$model::onlyTrashed()->where('soft_deleted', '<', $limitDate)->forceDelete();
        }
    }

    public function anonymizeModels($modelToAnonymize) {
        $today = \Carbon\Carbon::now();
        foreach($modelToAnonymize as $model=>$data) {
            $this->info('Anonymize model : '.(string) $model);
            $time = $data['nb_day'] ?? 7;
            $column = $data['column'] ?? 'updated_at';
            $scope = $data['scope'] ?? null;
            if($time == 0) $time = 7;
            $limitDate = $today->copy()->subDays($time);
            $model = $model::where($column, '<', $limitDate);
            if($scope) {
                $model = $model->$scope();
            }
            \Log::info($model->get()->pluck('id')->toArray());
            $this->info('qty : '.$model->count());
            $model->get()->each->wakAnonymize();
            //$this->table([['ids']], [$model->get()->pluck('id')->toArray()]);
            //$model::onlyTrashed()->where('soft_deleted', '<', $limitDate)->forceDelete();
        }
    }

    protected function utilPurgeUploads()
    {
        // if (!$this->confirmToProceed('This will PERMANENTLY DELETE files in the uploads directory that do not exist in the "system_files" table.')) {
        //     return;
        // }

        $uploadsDisk = Config::get('cms.storage.uploads.disk', 'local');
        if ($uploadsDisk !== 'local') {
            $this->error("Purging uploads is only supported on the 'local' disk, current uploads disk is $uploadsDisk");
            return;
        }

        $totalCount = 0;
        $validFiles = FileModel::pluck('disk_name')->all();
        $uploadsPath = Config::get('filesystems.disks.local.root', storage_path('app')) . '/' . Config::get('cms.storage.uploads.folder', 'uploads');

        // Recursive function to scan the directory for files and ensure they exist in system_files.
        $purgeFunc = function ($targetDir) use (&$purgeFunc, &$totalCount, $uploadsPath, $validFiles) {
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

                    // Skip files unable to be purged
                    if (!is_writeable($file)) {
                        $this->warn('Unable to purge file: ' . str_replace($uploadsPath, '', $file));
                        continue;
                    }

                    // Skip valid files
                    if (in_array(basename($file), $validFiles)) {
                        $this->warn('Skipped file in use: '. str_replace($uploadsPath, '', $file));
                        continue;
                    }
                    if($this->execute) {
                        unlink($file);
                        $this->info('Purged: '. str_replace($uploadsPath, '', $file));
                    } else {
                        $this->info('Will be Purged: '. str_replace($uploadsPath, '', $file));
                    }
                    $totalCount++;
                }
            }
        };

        $purgeFunc($uploadsPath);

        if ($totalCount > 0 && $this->execute) {
            $this->comment(sprintf('Successfully deleted %d invalid file(s), leaving %d valid files', $totalCount, count($validFiles)));
        } elseif($totalCount > 0 && !$this->execute) {
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
            ['execute', null, InputOption::VALUE_NONE, 'Executer le clean sinon affiche en log uniquement'],
            ['cleanFile', null, InputOption::VALUE_NONE, 'Execute le clean des files'],
        ];
    }
}
