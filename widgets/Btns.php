<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;
use October\Rain\Support\Collection;

class Btns extends WidgetBase
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'production';

    public $config;
    public $model;
    public $fields;
    public $format;
    public $context;
    public $user;

    public function prepareComonVars($context)
    {
        $this->context = $context;
        $this->vars['context'] = $this->context;
        $this->vars['modelClass'] = str_replace('\\', '\\\\', $this->config->modelClass);
        $this->vars['user'] = $this->user = \BackendAuth::getUser();
        $this->vars['hasWorkflow'] = $this->config->workflow;
    }

    public function renderBar($context = 'update', $mode = 'update', $modelId = null)
    {
        $this->prepareComonVars($context);
        $configBtns = $this->config->action_bar['config_btns'] ?? null;
        $this->vars['mode'] = $mode;
        $this->vars['partials'] = $this->config->action_bar['partials'] ?? null;
        if ($mode == 'update') {
            $model = $this->controller->formGetModel();
            $this->vars['btns'] = $this->getBtns($configBtns);
            $this->vars['modelId'] = $model->id;
            return $this->makePartial('action_bar');
        } else {
            $this->vars['btns'] = $this->getBtns($configBtns, true);
            $this->vars['modelId'] = $modelId;
            return $this->makePartial('action_container');
        }
    }

    public function renderWorkflowOrBtn($context = null)
    {
        if($context == 'preview') {
            return null;
        }
        $this->prepareComonVars($context);
        $hasWorkflow = $this->config->workflow;
        $hasWorkflow = $this->config->workflow;
        if ($hasWorkflow) {
            $model = $this->controller->formGetModel();
            $this->vars['noRole'] = $model->hasNoRole();
            $this->vars['transitions'] = $this->getWorkFlowTransitions();
            return $this->makePartial('workflow/workflow_part');
        } else {
            return $this->makePartial('sub/base_buttons');
        }
    }

    public function renderBreadcrump($context = null)
    {
        $this->prepareComonVars($context);
        if ($this->config->breadcrump) {
            $this->vars['breadcrump'] = $this->config->breadcrump;
            return $this->makePartial('breadcrump');
        } else {
            return '';
        }
    }

    public function renderToolBar($context = null, $secondaryLabel = false)
    {
        $this->prepareComonVars($context);
        $toolBar = null;
        if (!$secondaryLabel) {
            $toolBar = $this->config->tool_bar;
        } else {
            $toolBar = $this->config->tool_bar['secondary'][$secondaryLabel] ?? false;
            if (!$toolBar) {
                throw new \ApplicationException('La bare secondary est mal configure dans config_btns');
            }
        }
        //trace_log($toolBar);
        $base = $toolBar['base'] ?? false;
        if($base) {
            $base = $this->getPermissions($base);
            //trace_log($base);
        }
        $this->vars['base'] = $base;
        $this->vars['isLot'] = true;
        $this->vars['hasLot'] = $toolBar['config_lot']['btns'] ?? false;
        $this->vars['partials'] = $toolBar['partials'] ?? null;
        $this->vars['btns'] = $this->getBtns($toolBar['config_btns'] ?? null);
        return $this->makePartial('tool_bar');
    }
    private function getPermissions($btns) {
        $btnWithPermission = [];
        foreach($btns as $key=>$btn) {
            $permissionGranted = false;
            
            $permission = $btn['permissions'] ?? null;
            //trace_log($permission);
            if(!$permission) {
               $permissionGranted = true;
            } else {
                $permissionGranted = $this->user->hasAccess($permission);
            }
            //trace_log($permissionGranted);
            $btn['permissions']  = $permissionGranted;
            $btnWithPermission[$key] = $btn;
        }
        return $btnWithPermission;
    }

    public function renderLot()
    {
        $this->prepareComonVars('list');
        $configBtns = $this->config->tool_bar['lot'] ?? null;
        $this->vars['hasWorkflow'] = $this->config->workflow;
        $this->vars['btns'] = $this->getBtns($this->config->tool_bar['config_lot'] ?? null);
        return $this->makePartial('container_lot');
    }

    public function getBtns($configurator, $isInContainer = false)
    {
        // if($this->context != 'update') {
        //     return [];
        // }
        if (!$configurator) {
            return null;
        }
        $btns = [];
        $groups = $configurator['groups'] ?? [];
        $collection = new Collection($configurator['btns']);

        //Nettoyage des boutons, suppresion de ceux qui n'ont pas d'ajaxInline pour les boutons dans l'action container
        if($isInContainer) {
            $collection = $collection->reject(function ($item) {
                $configFromPlugins = \Config::get($item['config']);
                $ajaxInlineCaller = $configFromPlugins['ajaxInlineCaller'] ?? false;
                return !$ajaxInlineCaller;
            });
        }

        //nettoyage permissions
        //trace_log($collection->toArray());
        $collection = $collection->reject(function ($item) {
                $configFromPlugins = \Config::get($item['config']);
                $mergedConfig = array_merge($configFromPlugins, $item);
                $permissions = $mergedConfig['permissions'] ?? false;
                if(!$permissions) {
                    //Si il n' y a pas de jeux de permission on ne rejete rien
                    return false;
                }
                //trace_log($permissions);
                //trace_log($this->user->login);
                //trace_log($this->user->hasAccess($permissions));
                $this->user->hasAccess($permissions);               
                if (!$this->user->hasAccess($permissions)) {
                    //Pas de permission donc true on reject
                     //trace_log("rejet");
                    return true;
                } else {
                    //trace_log("Je ne rejete pas");
                    return false;
                }
            });
        //trace_log($collection->toArray());
        $format = $configurator['format'] ?? 'unique';
        if ($format == 'all') {
            //Création des boutons séparés
            foreach ($collection->toArray() as $key => $prod) {
                $configFromPlugins = \Config::get($prod['config']);
                $btns[$key] = $configFromPlugins;
                foreach ($prod as $keyopt => $opt) {
                    $btns[$key][$keyopt] = $opt;
                }
            }
        } elseif ($format == 'grouped') {
            //Création des boutons groupé
            foreach ($groups as $key => $icon) {
                $subbtns = $collection->where('group', $key)->toArray();
                $collection = $collection->diffKeys($subbtns);
                $sub = [];
                foreach ($subbtns as $subkey => $prod) {
                    $configFromPlugins = \Config::get($prod['config']);
                    $sub[$subkey] = $configFromPlugins;
                    foreach ($prod as $keyopt => $opt) {
                        $sub[$subkey][$keyopt] = $opt;
                    }
                }
                if (count($sub)) {
                    $btns[$key] = [
                        'label' => $key,
                        'icon' => $icon,
                        'btns' => $sub,
                    ];
                }
            }
            foreach ($collection->toArray() as $key => $prod) {
                $configFromPlugins = \Config::get($prod['config']);
                $btns[$key] = $configFromPlugins;
                foreach ($prod as $keyopt => $opt) {
                    $btns[$key][$keyopt] = $opt;
                }
            }
        } else {
            $subBtn = [];
            foreach ($collection->toArray() as $key => $prod) {
                $configFromPlugins = \Config::get($prod['config']);
                $subBtn[$key] = $configFromPlugins;
                foreach ($prod as $keyopt => $opt) {
                    $subBtn[$key][$keyopt] = $opt;
                }
            }
            if (count($subBtn)) {
                $btns[0] = [
                    'label' => 'Production & outils',
                    'icon' => '',
                    'btns' => $subBtn,
                ];
            }
        }
        return $btns;
    }

    public function loadAssets()
    {
        //$this->addCss('css/sidebarinfo.css', 'Waka.Utils');
    }

    public function getWorkFlowTransitions($withHidden = false)
    {

        $model = $this->controller->formGetModel();
        $transitions = $model->workflow_get()->getEnabledTransitions($model);
        $workflowMetadata = $model->workflow_get()->getMetadataStore();
        $objTransition = [];
        foreach ($transitions as $transition) {
            $hidden = $workflowMetadata->getMetadata('hidden', $transition) ?? false;
            if (!$hidden) {
                $name = $transition->getName();
                $label = $workflowMetadata->getMetadata('label', $transition) ?? $name;
                $com = $workflowMetadata->getMetadata('com', $transition) ?? null;
                $redirect = $workflowMetadata->getMetadata('redirect', $transition) ?? null;
                $icon = $workflowMetadata->getMetadata('icon', $transition) ?? null;
                $object = [
                    'value' => $name,
                    'label' => \Lang::get($label),
                    'com' => $com,
                    'icon' => $icon,
                    'redirect' => $redirect,
                ];
                array_push($objTransition, $object);
            }
        }
        return $objTransition;
    }
}
