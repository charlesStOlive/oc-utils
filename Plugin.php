<?php namespace Waka\Utils;

use Backend;
use Event;
use Lang;
use Mexitek\PHPColors\Color;
use System\Classes\CombineAssets;
use System\Classes\PluginBase;
use View;
use Illuminate\Foundation\AliasLoader;
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
                'snakeCase' => function ($twig) {
                    return snake_case($twig);
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
                'ident' => function ($string, $number) {
                    $number = $number * 4;
                    $spaces = str_repeat(' ', $number);
                    return rtrim(preg_replace('#^(.+)$#m', sprintf('%1$s$1', $spaces), $string));
                },
                'getContent' => function ($twig, $code, $column) {
                    $content = $twig->getContent($code);
                    return  $content[$column] ?? null;
                },
                'getRecursiveContent' => function ($twig, $code,$column) {
                    $content = $twig->getResursiveContent($code);
                    return  $content[$column] ?? null;
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
                'stubCreator' => function ($template, $allData, $secificData, $dataName = null) {
                    $allData['specific'] = $secificData;
                    $allData['dataName'] = $dataName;
                    //trace_log('stubCreator');
                    //trace_log($allData['specific']);
                    //trace_log($allData['dataName']);
                    $templatePath = plugins_path('waka/utils/console/'.$template);
                    $templateContent = \File::get($templatePath);
                    $content = \Twig::parse($templateContent, $allData);
                    return $content;
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
        $alias = AliasLoader::getInstance();
        $alias->alias('DataSources', 'Waka\Utils\Facades\DataSources');

        \App::singleton('datasources', function() {
            return new \Waka\Utils\Classes\Ds\DataSources;
        });

        \Event::listen('backend.page.beforeDisplay', function($controller, $action, $params) {
            $controller->addJs('/plugins/waka/utils/assets/js/froala.js');
            $controller->addJs('/plugins/waka/utils/assets/js/clipboard.min.js');
            $controller->addCss('/plugins/wcli/wconfig/assets/css/waka.css');
            
        });

        //Foralaedotor
        \Backend\Classes\Controller::extend(function($controller) {
            
        });

        $this->registerConsoleCommand('waka.injector', 'Waka\Utils\Console\CreateInjector');
        $this->registerConsoleCommand('waka.mc', 'Waka\Utils\Console\CreateModelController');
        $this->registerConsoleCommand('waka.uicolors', 'Waka\Utils\Console\CreateUiColors');
        $this->registerConsoleCommand('waka.workflow', 'Waka\Utils\Console\WorkflowCreate');
        $this->registerConsoleCommand('waka.workflowDump', 'Waka\Utils\Console\WorkflowDump');
        //$this->registerConsoleCommand('waka:workflowODump', 'Waka\Utils\Console\WorkflowOnlineDump');
        $this->registerConsoleCommand('waka:trad', 'Waka\Utils\Console\PluginTrad');
        $this->registerConsoleCommand('waka:excelTrad', 'Waka\Utils\Console\ExcelTrad');
        $this->registerConsoleCommand('waka:createSeed', 'Waka\Utils\Console\CreateSeedsFiles');
        $this->registerConsoleCommand('waka.ruleAsk', 'Waka\Utils\Console\CreateRuleAsk');
        $this->registerConsoleCommand('waka.ruleFnc', 'Waka\Utils\Console\CreateRuleFnc');
        $this->registerConsoleCommand('waka:cleanModels', 'Waka\Utils\Console\CleanModels');
        
        
    }

    public function registerWakaRules()
    {
        return [
            'asks' => [
                ['\Waka\Utils\WakaRules\Asks\LabelAsk'], 
                ['\Waka\Utils\WakaRules\Asks\HtmlAsk'], 
                ['\Waka\Utils\WakaRules\Asks\ImageAsk'],
                ['\Waka\Utils\WakaRules\Asks\FileImgLinked'],
                ['\Waka\Utils\WakaRules\Asks\Content'], 
            ],
            'fncs' => [
            ],
            'conditions' => [
                ['\Waka\Utils\WakaRules\Conditions\BackUser'], 
                ['\Waka\Utils\WakaRules\Conditions\ModelValue'], 
                ['\Waka\Utils\WakaRules\Conditions\ModelExist'], 
            ],
            'contents' => [
                ['\Waka\Utils\WakaRules\Contents\Html'], 
                ['\Waka\Utils\WakaRules\Contents\Md'], 
                ['\Waka\Utils\WakaRules\Contents\Vimeo'], 
                ['\Waka\Utils\WakaRules\Contents\ComonPartials'], 
            ]
        ];
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
            'datasource' => function ($code) {
                return \DataSources::getLabel($code);
            },
            'workflow' => [WorkflowColumn::class, 'render'],
            'raw' => function ($value) {
                return $value;
            },
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
            'Waka\Utils\FormWidgets\ImageWidget' => 'imagewidget',
            'Waka\Utils\FormWidgets\Workflow' => 'workflow',
            'Waka\Utils\FormWidgets\ModelInfo' => 'modelinfo',
            'Waka\Utils\FormWidgets\AskBuilder' => 'askbuilder',
            'Waka\Utils\FormWidgets\FncBuilder' => 'fncbuilder',
            'Waka\Utils\FormWidgets\RuleBuilder' => 'rulebuilder',
            'Waka\Utils\FormWidgets\Attributs' => 'attributs',
            'Waka\Utils\FormWidgets\ComonBlocs' => 'comonblocs',
            'Waka\Utils\FormWidgets\Lists' => 'lists',
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

        // $alias = AliasLoader::getInstance();
        // $alias->alias('Excel', '\\Maatwebsite\\Excel\\Facades\\Excel');

        

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


        
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            
        ];
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
            'waka.rules.asks.user' => [
                'tab' => 'Waka - Utils',
                'label' => "Utilisateur des ASKS",
            ],
            'waka.rules.asks.admin' => [
                'tab' => 'Waka - Utils',
                'label' => "Administrateur des ASKS",
            ],
            'waka.rules.fncs.user' => [
                'tab' => 'Waka - Utils',
                'label' => "Utilisateur des FNCS",
            ],
            'waka.rules.fncs.admin' => [
                'tab' => 'Waka - Utils',
                'label' => "Adminsitrateur des FNCS",
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
            // 'joblists' => [
            //     'label' => Lang::get('waka.utils::lang.menu.job_list'),
            //     'description' => Lang::get('waka.utils::lang.menu.job_list_description'),
            //     'category' => Lang::get('waka.utils::lang.menu.settings_category'),
            //     'icon' => 'icon-tasks',
            //     'url' => Backend::url('waka/utils/joblists'),
            //     'order' => 170,
            //     'counter' => 10,
            //     'permissions' => ['waka.jobList.admin'],
            // ],

        ];
    }
}
