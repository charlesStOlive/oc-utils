<?php namespace Waka\Utils;

use Backend;
use Event;
use Lang;
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
                    $dataFromConfig = \Config('waka.wconfig::' . $config_name);
                    //trace_log($dataFromConfig);
                    return $dataFromConfig;

                },
                'colorArray' => function ($twig, $color1) {
                    $colorArray = [];
                    return $colorArray;
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
        //$this->registerConsoleCommand('waka.workflowOnline', 'Waka\Utils\Console\WorkflowOnlineCreate');
        $this->registerConsoleCommand('waka.workflowOnlineCreate', 'Waka\Utils\Console\WorkflowOnlineDump');
        CombineAssets::registerCallback(function ($combiner) {
            $combiner->registerBundle('$/waka/wconfig/assets/css/simple_grid/pdf.less');
            $combiner->registerBundle('$/waka/wconfig/assets/css/simple_grid/email.less');
        });
    }

    public function registerListColumnTypes()
    {
        return [
            'waka-btn-actions' => [BtnActions::class, 'render'],
            'waka-calcul' => [CalculColumn::class, 'render'],
            'euro' => function ($value) {return number_format($value, 2, ',', ' ') . ' €';},
            'euro-int' => function ($value) {return number_format($value, 0, ',', ' ') . ' €';},
            'datasource' => function ($value) {return DataSourceList::getValue($value);},
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
        \Storage::extend('utils_gd_backup', function ($app, $config) {
            $client = new \Google_Client();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);
            $service = new \Google_Service_Drive($client);
            $adapter = new \Hypweb\Flysystem\GoogleDrive\GoogleDriveAdapter($service, $config['folderId']);

            return new \League\Flysystem\Filesystem($adapter);
        });

        /**
         * POur le copier coller
         */
        Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
            $controller->addCss('/plugins/waka/utils/assets/css/notification.css');
            $user = \BackendAuth::getUser();
            if ($user->hasAccess('waka.jobList.*') && Settings::get('activate_task_btn')) {
                $pluginUrl = url('/plugins/waka/utils');
                \Block::append('body', '<script type="text/javascript" src="' . $pluginUrl . '/assets/js/backendnotifications.js"></script>');
            }

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
        });

        Event::listen('backend.down.rapidLinks', function ($controller) {
            $model = $controller->formGetModel();
            if (!$model->rapidLinks) {
                throw new \ApplicationException("l'attributs rapidLinks ( getRapidLinksAttribute)  est manquant dans " . get_class($model));
            }
            return View::make('waka.utils::rapidLinks')->withLinks($model->rapidLinks);
        });
        Event::listen('backend.top.update', function ($controller) {
            if (in_array('Waka.Utils.Behaviors.DuplicateModel', $controller->implement)) {
                $model = $controller->formGetModel();
                return View::make('waka.utils::duplicatebutton')->withId($model->id);
            }

        });
        Event::listen('popup.actions.tools', function ($controller, $model, $id) {
            if (in_array('Waka.Utils.Behaviors.DuplicateModel', $controller->implement)) {
                return View::make('waka.utils::duplicatebuttoncontent')->withId($id);
            }

        });
        Event::listen('backend.top.index', function ($controller) {
            $user = \BackendAuth::getUser();
            //trace_log($user->hasAccess('waka.importexport.imp.*'));

            // if (!$user->hasAccess('waka.importexport.imp.*')) {
            //     //trace_log("false");
            //     return;
            // }
            //trace_log("ok");
            if (in_array('Waka.Utils.Behaviors.TraitementsLots', $controller->implement)) {
                return View::make('waka.utils::lotsbutton');
            }
        });
        Event::listen('job.create.*', function ($event, $params) {
            $userId = \BackendAuth::getUser()->id;
            $jobId = $params[0];
            $name = $params[1];
            $joblist = new Models\JobList();
            $joblist->id = $jobId;
            $joblist->user_id = $userId;
            $joblist->name = $name;
            $joblist->state = 'Attente';
            $joblist->save();
        });

        // Ecouteur de job et enregistrement
        Event::listen('job.start.*', function ($event, $params) {
            $job = $params[0];
            $name = $params[1];
            //trace_log("evenement job.start");
            //trace_log($job->getJobId());
            //trace_log($name);
            $id = $job->getJobId();
            $joblist = Models\JobList::find($id);
            if (!$joblist) {
                return;
            }

            if ($name) {
                $joblist->name = $name;
            }
            $joblist->payload = $job->payload();
            $joblist->attempts = $job->attempts();
            $joblist->state = 'En cours';
            $joblist->started_at = date("Y-m-d H:i:s");
            $joblist->save();
        });
        Event::listen('job.end.*', function ($event, $params) {
            $job = $params[0];
            $id = $job->getJobId();
            $joblist = Models\JobList::find($id);
            if (!$joblist) {
                return;
            }

            $joblist->end_at = date("Y-m-d H:i:s");
            $joblist->state = 'Terminé';
            $joblist->save();

        });
        \Queue::failing(function ($jobFailed) {
            $id = $jobFailed->job->getJobId();
            $joblist = Models\JobList::find($id);
            if (!$joblist) {
                /* */trace_log("job inconnu! : " . $jobFailed->job->getJobId());
                return;
            }
            $joblist->end_at = date("Y-m-d H:i:s");
            $joblist->state = 'Erreur';
            $joblist->errors = $jobFailed->exception;

            $joblist->save();
            //
        });
        // Event::listen('job.error.*', function ($error) {
        //     //trace_log("Listen : job.error.*");
        //     //trace_log($error);
        // });

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
        $showNotification = true;

        if (!Settings::get('activate_task_btn')) {
            return [];
        }

        return [
            'notification' => [
                'label' => Lang::get("waka.utils::lang.menu.job_list_s"),
                'url' => Backend::url('waka/utils/joblists'),
                'icon' => 'icon-refresh',
                'order' => 500,
                'counter' => 0,
                'permissions' => ['waka.jobList.*'],
                'counterLabel' => Lang::get('waka.utils::lang.joblist.btn_counter_label'),
            ],
        ];
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
                'permissions' => ['waka.wconfig.admin'],
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
