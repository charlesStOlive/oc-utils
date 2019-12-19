<?php namespace Waka\Utils\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use System\Classes\SettingsManager;

/**
 * Data Sources Back-end Controller
 */
class DataSources extends Controller
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
        //BackendMenu::setContext('Waka.Publisher', 'publisher', 'side-menu-models');
        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Waka.Utils', 'datasources');
    }
}
