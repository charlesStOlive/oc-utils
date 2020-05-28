<?php namespace Waka\Utils;

use Backend;
use Event;
use Lang;
use System\Classes\PluginBase;
use View;
use Waka\Utils\Columns\BtnActions;
use Waka\Utils\Columns\CalculColumn;

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
                'toJson' => function ($twig) {
                    return json_encode($twig);

                },
                'defaultConfig' => function ($twig, $config_name) {
                    $dataFromConfig = \Config('waka.crsm::' . $config_name);
                    trace_log($dataFromConfig);
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
        $this->registerConsoleCommand('waka.model', 'Waka\Utils\Console\CreateModel');
        $this->registerConsoleCommand('waka.injector', 'Waka\Utils\Console\CreateInjector');
        $this->registerConsoleCommand('waka.controller', 'Waka\Utils\Console\CreateController');
        $this->registerConsoleCommand('waka.content', 'Waka\Utils\Console\CreateContent');
        $this->registerConsoleCommand('waka.pluginversionshift', 'Waka\Utils\Console\PluginVersionShift');

    }

    public function registerListColumnTypes()
    {
        return [
            'waka-btn-actions' => [BtnActions::class, 'render'],
            'waka-calcul' => [CalculColumn::class, 'render'],
            'euro' => function ($value) {return number_format($value, 2, ',', ' ') . ' €';},
            'euro-int' => function ($value) {return number_format($value, 0, ',', ' ') . ' €';},
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
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        /**
         * POur le copier coller
         */
        // Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
        //     $controller->addJs('/plugins/waka/utils/assets/js/clipboard.min.js');
        // });
        Event::listen('backend.down.rapidLinks', function ($controller) {
            $model = $controller->formGetModel();
            if (!$model->rapidLinks) {
                throw new \ApplicationException("l'attributs rapidLinks ( getRapidLinksAttribute)  est manquant dans " . get_class($model));
            }
            return View::make('waka.utils::rapidLinks')->withLinks($model->rapidLinks);
        });
        Event::listen('backend.update.duplicate', function ($controller) {
            if (in_array('Waka.Utils.Behaviors.DuplicateModel', $controller->implement)) {
                $model = $controller->formGetModel();
                return View::make('waka.utils::duplicatebutton')->withId($model->id);
            }

        });
        Event::listen('popup.actions.line1', function ($controller, $model, $id) {
            if (in_array('Waka.Utils.Behaviors.DuplicateModel', $controller->implement)) {
                return View::make('waka.utils::duplicatebuttoncontent')->withId($id);
            }

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
            'Waka\Utils\Components\GestionKey' => 'gestionKey',
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
            'waka.datasource.user.admin' => [
                'tab' => 'Waka - Utils',
                'label' => 'Administrateur des Data Sources',
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
            'data_sources' => [
                'label' => Lang::get('waka.utils::lang.menu.data_sources'),
                'description' => Lang::get('waka.utils::lang.menu.data_sources_description'),
                'category' => Lang::get('waka.utils::lang.menu.settings_category'),
                'icon' => 'icon-paper-plane',
                'url' => Backend::url('waka/utils/datasources'),
                'order' => 1,
                'permissions' => ['waka.datasource.admin'],
            ],
        ];
    }
}
