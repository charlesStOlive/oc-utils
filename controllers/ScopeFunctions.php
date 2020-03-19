<?php namespace Waka\Utils\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Scope Functions Back-end Controller
 */
class ScopeFunctions extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Waka.Utils', 'utils', 'scopefunctions');
    }
}
