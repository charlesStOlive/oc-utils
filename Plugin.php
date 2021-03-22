<?php namespace Waka\Utils;

use Backend;
use Event;
use Lang;
use Mexitek\PHPColors\Color;
use System\Classes\CombineAssets;
use System\Classes\PluginBase;
use View;
use Waka\Utils\Classes\DataSourceList;
use Waka\Utils\Columns\BtnActions;
use Waka\Utils\Columns\CalculColumn;
use Waka\Utils\Columns\WorkflowColumn;
use Waka\Utils\Models\Settings;

/**
 * Utils Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Utils',
            'description' => 'No description provided yet...',
            'author' => 'Waka',
            'icon' => 'icon-leaf',
        ];
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'localeDate' => [new \Waka\Utils\Classes\WakaDate, 'localeDate'],
                'toJson' => function ($twig) {
                    return json_encode($twig);
                },
                'workflow' => function ($twig) {
                    return $twig->wfPlaceLabel();
                },
                'camelCase' => function ($twig) {
                    return camel_case($twig);
                },
                'defaultConfig' => function ($twig, $config_name) {
                    $dataFromConfig = \Config('wcli.wconfig::' . $config_name);
                    //trace_log($dataFromConfig);
                    return $dataFromConfig;
                },
                'colorArray' => function ($twig, $color1) {
                    $colorArray = [];
                    return $colorArray;
                },
            ],
            'functions' => [
                // Using an inline closure
                'getColor' => function ($color, $mode = "rgba", $transform = null, $factor = 0.1) {
                    //trace_log($color);
                    $color = new Color($color);
                    switch ($transform) {
                        case 'complementary':
                            $color = $color->complementary();
                            break;
                        case 'lighten':
                            $color = $color->lighten($factor);
                            break;
                        case 'darken':
                            $color = $color->darken($factor);
                            break;
                    }
                    $finalColor = $color;
                    if (is_string($color)) {
                        $finalColor = new Color($color);
                    }
                    switch ($mode) {
                        case 'rgba':
                            return $finalColor->getRgb();
                        case 'string':
                            return '#' . $finalColor->getHex();
                    }
                },
            ],
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommand('waka.injector', 'Waka\Utils\Console\CreateInjector');
        $this->registerConsoleCommand('waka.mc', 'Waka\Utils\Console\CreateModelController');
        $this->registerConsoleCommand('waka.uicolors', 'Waka\Utils\Console\CreateUiColors');
        $this->registerConsoleCommand('waka.workflow', 'Waka\Utils\Console\WorkflowCreate');
        $this->registerConsoleCommand('waka.workflowDump', 'Waka\Utils\Console\WorkflowDump');
        $this->registerConsoleCommand('waka:trad', 'Waka\Utils\Console\PluginTrad');
        //$this->registerConsoleCommand('waka.workflowOnline', 'Waka\Utils\Console\WorkflowOnlineCreate');
        $this->registerConsoleCommand('waka.workflowOnlineCreate', 'Waka\Utils\Console\WorkflowOnlineDump');
        CombineAssets::registerCallback(function ($combiner) {
            $combiner->registerBundle('$/waka/utils/assets/css/waka.less');
            $combiner->registerBundle('$/wcli/wconfig/assets/css/simple_grid/pdf.less');
            $combiner->registerBundle('$/wcli/wconfig/assets/css/simple_grid/email.less');
        });
    }

    public function registerListColumnTypes()
    {
        return [
            'waka-btn-actions' => [BtnActions::class, 'render'],
            'waka-calcul' => [CalculColumn::class, 'render'],
            'euro' => function ($value) {
                return number_format($value, 2, ',', ' ') . ' €';
            },
            'euro-int' => function ($value) {
                return number_format($value, 0, ',', ' ') . ' €';
            },
            'datasource' => function ($value) {
                return DataSourceList::getValue($value);
            },
            'workflow' => [WorkflowColumn::class, 'render'],
        ];
    }

    public function registerFormWidgets(): array
    {
        return [
            'Waka\Utils\FormWidgets\ColorPickerAnalyser' => 'colorpickeranalyser',
            'Waka\Utils\FormWidgets\ColorPickerCloudi' => 'colorpickercloudi',
            'Waka\Utils\FormWidgets\CommentField' => 'commentfield',
            'Waka\Utils\FormWidgets\LabelList' => 'labellist',
            'Waka\Utils\FormWidgets\FunctionsList' => 'functionslist',
            'Waka\Utils\FormWidgets\ScopesList' => 'scopeslist',
            'Waka\Utils\FormWidgets\ImagesList' => 'imageslist',
            'Waka\Utils\FormWidgets\Workflow' => 'workflow',
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Event::listen('backend.page.beforeDisplay', function($controller, $action, $params) {
            //trace_log('/plugins/waka/utils/assets/css/waka.css');
            $controller->addCss('/plugins/waka/utils/assets/css/waka.css');
        });

        \Storage::extend('utils_gd_backup', function ($app, $config) {
            $client = new \Google_Client();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);
            $service = new \Google_Service_Drive($client);
            $adapter = new \Hypweb\Flysystem\GoogleDrive\GoogleDriveAdapter($service, $config['folderId']);

            return new \League\Flysystem\Filesystem($adapter);
        });

        

        \Event::listen('backend.menu.extendItems', function ($navigationManager) {
            //trace_log($navigationManager->getActiveMainMenuItem());
            if (!Settings::get('activate_dashboard')) {
                $navigationManager->removeMainMenuItem('October.Backend', 'dashboard');
            }
            if (!Settings::get('activate_cms')) {
                $navigationManager->removeMainMenuItem('October.Cms', 'cms');
            }
            if (!Settings::get('activate_builder')) {
                $navigationManager->removeMainMenuItem('RainLab.Builder', 'builder');
            }
            if (!Settings::get('activate_user_btn')) {
                $navigationManager->removeMainMenuItem('RainLab.User', 'user');
            }
            if (!Settings::get('activate_builder')) {
                $navigationManager->removeMainMenuItem('RainLab.Builder', 'builder');
            }
            if (!Settings::get('activate_media_btn')) {
                $navigationManager->removeMainMenuItem('October.Backend', 'media');
            }
        });

        Event::listen('backend.tools', function ($controller) {
            $model = $controller->formGetModel();
            if ($model->rapidLinks) {
                return View::make('waka.utils::rapidLinks')->withLinks($model->rapidLinks);
            }
        });
        
        /**
         * POUR LE WORKFLOW COLUMN
         */
        Event::listen('backend.list.extendColumns', function ($widget) {
            /** @var \Backend\Widgets\Lists $widget */
            foreach ($widget->config->columns as $name => $config) {
                if (empty($config['type']) || $config['type'] !== 'workflow') {
                    continue;
                }
                // Store field config here, before that unofficial fields was removed
                WorkflowColumn::storeFieldConfig($name, $config);
            }
        });

        \System\Controllers\Settings::extend(function ($controller) {
            $controller->addDynamicMethod('onWconfigImport', function () use ($controller) {
                $user = \BackendAuth::getUser();
                if (!$user->isSuperUser()) {
                    return;
                }
                $startFile = \Waka\Utils\Models\Settings::get('start_file');
                if ($startFile) {
                    \Excel::import(new \Waka\ImportExport\Classes\Imports\SheetsImport, storage_path('app/media/' . $startFile));
                } else {
                    throw new \ApplicationException('Le fichier n a pas été trouvé');
                }
                return \Redirect::refresh();
            });
        });

        $localeCode = Lang::getLocale();
        setlocale(LC_TIME, $localeCode . '_' . strtoupper($localeCode) . '.UTF-8');
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'waka.datasource.admin' => [
                'tab' => 'Waka - Utils',
                'label' => 'Administrateur des Data Sources',
            ],
            'waka.jobList.admin' => [
                'tab' => 'Waka - Utils',
                'label' => "Administrateur des taches de l'app",
            ],
            'waka.jobList.user' => [
                'tab' => 'Waka - Utils',
                'label' => "Lecteur des taches de l'app",
            ],
        ];
    }

    public function registerNavigation()
    {
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerSettings()
    {
        return [
            'utils_settings' => [
                'label' => Lang::get('waka.utils::lang.settings.label'),
                'description' => Lang::get('waka.utils::lang.settings.description'),
                'category' => Lang::get('waka.utils::lang.menu.settings_category'),
                'icon' => 'icon-wrench',
                'class' => 'Waka\Utils\Models\Settings',
                'order' => 150,
                'permissions' => ['wcli.wconfig.admin'],
            ],
            'joblists' => [
                'label' => Lang::get('waka.utils::lang.menu.job_list'),
                'description' => Lang::get('waka.utils::lang.menu.job_list_description'),
                'category' => Lang::get('waka.utils::lang.menu.settings_category'),
                'icon' => 'icon-tasks',
                'url' => Backend::url('waka/utils/joblists'),
                'order' => 170,
                'counter' => 10,
                'permissions' => ['waka.jobList.admin'],
            ],

        ];
    }
}
