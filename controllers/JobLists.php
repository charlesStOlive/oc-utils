<?php namespace Waka\Utils\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Flash;
use Lang;
use System\Classes\SettingsManager;
use Waka\Utils\Models\JobList;

/**
 * Job Lists Back-end Controller
 */
class JobLists extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Waka.Utils', 'joblists');
    }

    public function index_onEmptyJobList()
    {
        if (JobList::where('state', '<>', "Terminé")->count()) {
            \Flash::error("Il y a encors des tâches en attente ou en cours");
            return $this->listRefresh();
        }
        JobList::truncate();
        Flash::success(Lang::get('Historique des taches supprimé'));
        return $this->listRefresh();
    }
    public function index_onRefresh()
    {
        return $this->listRefresh();
    }
}
