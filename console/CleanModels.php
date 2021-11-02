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
        foreach ($bundles as $bundle) {
            $bundleSoftDelete = $bundle['cleanSoftDelete'] ?? [];
            $sofDeleteToClean = array_merge($sofDeleteToClean, $bundleSoftDelete);
            //
            $deleteBundle = $bundle['delete'] ?? [];
            $modelToDelete = array_merge($modelToDelete, $deleteBundle);
           

        }
        $this->cleanSoftDeleteModels($sofDeleteToClean);
        $this->deleteModels($modelToDelete);
        //On purge les modèles inutiles.
        $this->utilPurgeUploads();
    }

    public function cleanSoftDeleteModels($modelsToClean) {
        $today = \Carbon\Carbon::now();
        
        foreach($modelsToClean as $model=>$time) {
            if($time == 0) $time = 7;
            $limitDate = $today->copy()->subDays($time);
            //trace_log($model::onlyTrashed()->where('deleted_at', '<', $limitDate)->get()->pluck('name')->toArray());
            //$model::onlyTrashed()->where('soft_deleted', '<', $limitDate)->forceDelete();
        }
    }

    public function deleteModels($modelsToDelete) {
        $today = \Carbon\Carbon::now();
        foreach($modelsToDelete as $model=>$data) {
            $time = $data['nb_day'] ?? 7;
            $column = $data['column'] ?? 'updated_at';
            $scope = $data['scope'] ?? null;
            if($time == 0) $time = 7;
            $limitDate = $today->copy()->subDays($time);
            $model = $model::where($column, '<', $limitDate);
            if($scope) {
                $model = $model->$scope();
            }
            //trace_log($model->get()->toArray());
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

                    unlink($file);
                    $this->info('Purged: '. str_replace($uploadsPath, '', $file));
                    $totalCount++;
                }
            }
        };

        $purgeFunc($uploadsPath);

        if ($totalCount > 0) {
            $this->comment(sprintf('Successfully deleted %d invalid file(s), leaving %d valid files', $totalCount, count($validFiles)));
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
        return [];
    }
}
