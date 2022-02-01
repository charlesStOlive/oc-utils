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
class CleanFiles extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'waka:cleanFiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permet de nettoyer les fichiers d\'upload';

    protected $executeClean = false;

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
            $this->executeClean = true;
        }
        $this->purgeFileOrphans();
        $this->utilPurgeUploads();
    }

    public function purgeFileOrphans() {
        $yesterday = \Carbon\Carbon::now()->subDay();
        $query = \System\Models\File::whereNull('field')->whereNull('attachment_id')->whereNull('attachment_type')->where('updated_at', '<', $yesterday);
        $count = $query->count();
        $this->info('Delete file orphans : '.$count);
        if($count && $this->executeClean) {
            $this->info('Netoyage file orphans');
            $query->get()->each->delete();
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
                    
                    
                    if($this->executeClean) {
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

        if ($totalCount > 0 && $this->executeClean) {
            $this->comment(sprintf('Successfully deleted %d invalid file(s), leaving %d valid files', $totalCount, count($validFiles)));
        } elseif($totalCount > 0 && !$this->executeClean) {
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
