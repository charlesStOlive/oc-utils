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
    protected $executeClean = false;


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
        if($this->option('exec')) {
            $this->executeClean = true;
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
    }

    public function cleanSoftDeleteModels($modelsToClean) {
        $today = \Carbon\Carbon::now();
        
        foreach($modelsToClean as $model=>$time) {
            $modelName = (string) $model;
            if($time == 0) $time = 7;
            $limitDate = $today->copy()->subDays($time);
            $query = $model::onlyTrashed()->where('deleted_at', '<', $limitDate);
            $count = $query->count();
            $this->info('Delete Soft delete model : '.$modelName.' qty '.$count);
            if($count && $this->executeClean) {
                $this->info('Netoyage de  : '.$modelName);
                $query->forceDelete();
            }
            //$query->->forceDelete();

        }
    }

    public function deleteModels($modelsToDelete) {
        $today = \Carbon\Carbon::now();
        foreach($modelsToDelete as $model=>$data) {
            $modelName = (string) $model;
            $time = $data['nb_day'] ?? 7;
            $column = $data['column'] ?? 'updated_at';
            $scope = $data['scope'] ?? null;
            if($time == 0) $time = 7;
            $limitDate = $today->copy()->subDays($time);
            $model = $model::where($column, '<', $limitDate);
            if($scope) {
                $model = $model->$scope();
            }
            $count = $model->count();
            $this->info('Delete model : '.$modelName.' qty '.$count);
            if($count && $this->executeClean) {
                $this->info('Netoyage de  : '.$modelName);
                $model->delete();
            }
        }
    }

    public function anonymizeModels($modelToAnonymize) {
        $today = \Carbon\Carbon::now();
        foreach($modelToAnonymize as $model=>$data) {
            $modelName = $model;
            // $this->info('Anonymize model : '.(string) $model);
            $time = $data['nb_day'] ?? 7;
            $column = $data['column'] ?? 'updated_at';
            $scope = $data['scope'] ?? null;
            if($time == 0) $time = 7;
            $limitDate = $today->copy()->subDays($time);
            $model = $model::where($column, '<', $limitDate);
            if($scope) {
                $model = $model->$scope();
            }
            $model = $model->notAnonymized();
            $count = $model->count();
            $this->info('Anonymize model : '.$modelName.' qty '.$count);
            if($count && $this->executeClean) {
                $this->info('Netoyage de  : '.$modelName);
                $model->get()->each->wakAnonymize();
            }
            
            //$this->table([['ids']], [$model->get()->pluck('id')->toArray()]);
            //$model::onlyTrashed()->where('soft_deleted', '<', $limitDate)->forceDelete();
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
            ['exex', null, InputOption::VALUE_NONE, 'Executer le clean sinon affiche en log uniquement'],
        ];
    }
}
