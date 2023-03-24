<?php

namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;
use Winter\Storm\Support\Collection;

class Btns extends WidgetBase
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'production';

    public $config;
    public $workflowConfigState;
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
        $this->vars['hasWorkflow'] = $this->config->workflow ? true : false;
        $this->model = $this->controller->formGetModel();
        $this->workflowConfigState = $this->getWorkflowConfigFromState($this->model);
    }

    public function getWorkflowConfigFromState($model = null)
    {
        if (!$model) {
            $model = $this->controller->formGetModel();
        }
        //trace_log($this->config->workflow);
        $state = $model?->state;
        $stateConfig = $this->config->workflow[$state] ?? null;
        if ($stateConfig) {
            return $stateConfig;
        } else {
            \Log::error('pas de config pour le state ' . $state);
            return [];
        }
    }

    public function renderBar($context = 'update', $mode = 'update', $modelId = null)
    {
        $this->prepareComonVars($context);
        $configBtns = $this->config->action_bar['config_btns'] ?? [];
        $this->vars['mode'] = $mode;
        //Est ce qu'il y a des partials à ajouter à la barre ?
        $this->vars['partials'] = $this->config->action_bar['partials'] ?? null;
        //Rendu en fonction du mode. le mode par default est à update mais il peut être une list dans les popup actions. 
        if ($mode == 'update') {
            $model = $this->controller->formGetModel();
            $this->vars['modelId'] = $model->id;
            $this->vars['btns'] = $this->getBtns($configBtns);
            return $this->makePartial('action_bar');
        } else {
            $this->vars['btns'] = $this->getBtns($configBtns, true);
            $this->vars['modelId'] = $modelId;
            return $this->makePartial('action_container');
        }
    }

    public function renderWorkflowOrBtn($context = null)
    {
        
        if ($context == 'preview') {
            return null;
        }
        $this->prepareComonVars($context);
        $hasWorkflow = $this->config->workflow;

        if (!$hasWorkflow) {
            return $this->makePartial('sub/base_buttons');
        }
        if (!$this->model->userHasWfPermission()) {
            return $this->makePartial('workflow/no_wf_role');
        }

        //BLOCK
        $block = $this->workflowConfigState['block'] ?? false;
        if ($block) {
            return $this->makePartial('workflow/no_wf_role');
        }
        $formAutoConfig = [];
        $formAutoConfig = $this->workflowConfigState['form_auto'] ?? [];
        if (!count($formAutoConfig)) {
            $formAutoConfig = $this->model->listWfPlaceFormAuto();
        }
        $wfTrys = $formAutoConfig;
        //Transitions-----------------------------------------
        $transitions = $this->getWorkFlowTransitions();
        //trace_log($transitions);
        $show_all_trans_if = $this->workflowConfigState['show_all_trans_if'] ?? false;
        $hide_all_trans = $this->workflowConfigState['hide_all_trans'] ?? false;
        $change_trans = $this->workflowConfigState['change_trans'] ?? [];
        $separate_all = $this->workflowConfigState['separate_all'] ?? false;
        trace_log($separate_all);
        //trace_log($this->workflowConfigState);
        //trace_log("separate_all : ".$separate_all);

        //trace_log($show_all_trans_if);
        //trace_log($hide_all_trans);
        //trace_log($change_trans);
        //----------
        $wfBtns = [];
        $wfBtnsBefore = [];
        $wfBtnsAfter = [];
        //Show ALL----------
        if ($show_all_trans_if) {
            if (!$this->user->hasPermission($show_all_trans_if)) $transitions = [];
        }
        //Hide All-------------
        if ($hide_all_trans) {
            $transitions = [];
        }
        //nettoyage des transitions avec le formAuto
        foreach ($transitions as $i => $transition) {
            $transKey = $transition['value'];
            if (in_array($transition['value'], $formAutoConfig)) {
                unset($transitions[$i]);
            }
        }
        //Réorganisation des transitions
        
        if ($change_trans) {
            foreach ($transitions as $i => $transition) {
                if ($ch = $change_trans[$transKey] ?? false) {
                    if ($ch['properties'] ?? false) {
                        $newProperties = [];
                        foreach($ch['properties'] as $skey=>$prop) {
                             $newProperties[$skey] = \Lang::get($prop);
                        }
                        //trace_log($newProperties);
                        $transitions[$i] = array_merge($transitions[$i], $newProperties);
                    }
                    if ($view = $ch['view'] ?? null) {
                        if ($view == 'btns_before') {
                            array_push($wfBtnsBefore, $transition);
                            unset($transitions[$i]);
                        } else if ($view =='btns_after') {
                            array_push($wfBtnsAfter, $transition);
                            unset($transitions[$i]);
                        } 
                    } 
                } 
            }
        } 
        $wfBtns = $transitions;
        if($separate_all) {
            //trace_log('separate_all');
            //trace_log($wfBtnsBefore);
            //trace_log($wfBtns);
            $wfBtnsBefore =  array_merge($wfBtnsBefore, $wfBtns);
            $wfBtns = [];
        }
        //trace_log($wfBtnsBefore);
        //trace_log($wfBtns);



        //Information venant du workflow on va réorganiser les boutons si besoin.

        $this->vars['mustTrans'] =  $this->model->wfMustTrans;
        $this->vars['separateFirst'] =  $this->workflowConfigState['separateFirst'] ?? false;
        $this->vars['modelId'] = $this->model->id;

        $this->vars['wfTrys'] = $wfTrys ? "try:'" . implode(',', $wfTrys) . "'" : null;
        $this->vars['wfBtns'] = $wfBtns;
        $this->vars['wfBtnsBefore'] = $wfBtnsBefore;
        $this->vars['wfBtnsAfter'] = $wfBtnsAfter;


        return $this->makePartial('workflow');
    }

    public function renderBreadcrump($context = null)
    {
        $this->prepareComonVars($context);
        $model = $this->controller->formGetModel();
        if (!$model) {
            return;
        }
        if ($this->config->breadcrump) {
            $configBreadCrump = $this->config->breadcrump;
            foreach ($configBreadCrump as $key => $config) {
                //trace_log($config);
                $splitUrl = explode(':', $config);
                $varInUrl = $splitUrl[1] ?? false;
                if ($varInUrl) {
                    //trace_log($splitUrl[1]);
                    //trace_log($splitUrl[0].$model->{$splitUrl[1]});
                    $configBreadCrump[$key] = $splitUrl[0] . $model->{$varInUrl};
                }
            }
            $this->vars['breadcrump'] = $configBreadCrump;
            return $this->makePartial('breadcrump');
        } else {
            return '';
        }
    }

    public function renderToolBar($secondaryLabel = false)
    {
        $this->prepareComonVars(null);
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
        if ($base) {
            $base = $this->getPermissions($base);
            //trace_log($base);
        }
        $this->vars['base'] = $base;
        $this->vars['isLot'] = true;
        $hasLot = $toolBar['config_lot']['btns'] ?? false;
        if ($hasLot) {
            $permissionLot = $this->config->tool_bar['config_lot']['permissions'] ?? null;
            //trace_log($permissionLot);
            if ($permissionLot) {
                if (!$this->user->hasAccess($permissionLot)) {
                    $hasLot = false;
                }
            }
        }
        $this->vars['hasLot'] = $hasLot;
        $this->vars['partials'] = $toolBar['partials'] ?? null;
        $this->vars['btns'] = $this->getBtns($toolBar['config_btns'] ?? null);
        return $this->makePartial('tool_bar');
    }
    private function getPermissions($btns)
    {
        //trace_log("getPermissions");
        $btnWithPermission = [];
        foreach ($btns as $key => $btn) {
            $permissionGranted = false;

            $permission = $btn['permissions'] ?? null;
            //trace_log($permission);
            if (!$permission) {
                $permissionGranted = true;
            } else {
                $permissionGranted = $this->user->hasAccess($permission);
            }
            //trace_log($btn);
            $btn['permissions']  = $permissionGranted;
            $btnWithPermission[$key] = $btn;
        }
        return $btnWithPermission;
    }

    public function renderLot()
    {
        $this->prepareComonVars('list');
        $this->vars['hasWorkflow'] = $this->config->workflow;
        $this->vars['btns'] = $this->getBtns($this->config->tool_bar['config_lot'] ?? null);
        return $this->makePartial('container_lot');
    }

    public function renderCallOut()
    {
        if ($hint = $this->workflowConfigState['hint'] ?? false) {
            $this->vars['hintTitle'] = $hint['title'] ?? 'Info';
            $this->vars['hintContent'] = \Lang::get($hint['content']);
            $this->vars['hintType'] = $hint['type'] ?? 'info';
            return $this->makePartial('callout');
        } else {
            return null;
        }
    }

    public function getBtns($configurator, $isInContainer = false)
    {

        if (!$configurator) {
            return null;
        }
        $btns = [];
        $groups = $configurator['groups'] ?? [];
        $collection = new Collection($configurator['btns']);
        //Blocage si dans les hide de la config
        if ($this->model) {
            $hiddenBar = $this->workflowConfigState['hide'] ?? false;
            if ($hiddenBar) {
                return [];
            }
        }


        //Nettoyage des boutons, suppresion de ceux qui n'ont pas d'ajaxInline pour les boutons dans l'action container
        //trace_log("Est dans un container : ".$isInContainer);
        if ($isInContainer) {
            $collection = $collection->reject(function ($item) {
                $configFromPlugins = \Config::get($item['config']);
                $ajaxInlineCaller = $configFromPlugins['ajaxInlineCaller'] ?? false;
                return !$ajaxInlineCaller;
            });
        }

        //nettoyage permissions
        $collection = $collection->reject(function ($item) {
            $configFromPlugins = \Config::get($item['config']);
            $mergedConfig = [];
            if ($configFromPlugins) {
                $mergedConfig = array_merge($configFromPlugins, $item);
            }

            $permissions = $mergedConfig['permissions'] ?? false;
            if (!$permissions) {
                //Si il n' y a pas de jeux de permission on ne rejete rien
                return false;
            }
            // trace_log($permissions);
            // trace_log($this->user->login);
            // trace_log($this->user->hasAccess($permissions));
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
        $format = $configurator['format'] ?? 'all';
        if ($format == 'all') {
            //Création des boutons séparés
            foreach ($collection->toArray() as $key => $prod) {
                $btns[$key] = $this->getConfigFromPlugins($key, $prod);
            }
        } elseif ($format == 'grouped') {
            //Création des boutons groupé
            foreach ($groups as $key => $icon) {
                $subbtns = $collection->where('group', $key)->toArray();
                $collection = $collection->diffKeys($subbtns);
                $sub = [];
                foreach ($subbtns as $subkey => $prod) {
                    $sub[$subkey] = $this->getConfigFromPlugins($subkey, $prod);
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
                $btn[$key] = $this->getConfigFromPlugins($key, $prod);
            }
        } else {
            $subBtn = [];
            foreach ($collection->toArray() as $key => $prod) {
                $subBtn[$key] = $this->getConfigFromPlugins($key, $prod);
            }
            if (count($subBtn)) {
                $btns[0] = [
                    'label' => 'Production & outils',
                    'icon' => '',
                    'btns' => $subBtn,
                ];
            }
        }
        //trace_log($btns);
        return $btns;
    }

    public function getConfigFromPlugins($key, $prod)
    {
        $config = \Config::get($prod['config']);
        if (!$config) {
            throw new  \SystemException('configuration introuvable pour : ' . $key);
        }
        $btn = $config;
        foreach ($prod as $keyopt => $opt) {
            $btn[$keyopt] = $opt;
        }
        return $btn;
    }

    public function loadAssets()
    {
        //$this->addCss('css/sidebarinfo.css', 'Waka.Utils');
    }

    public function getWorkFlowTransitions($withHidden = false)
    {

        $model = $this->controller->formGetModel();
        $transitions =  $model->getWakaWorkflow()->getEnabledTransitions($model);
        $objTransition = [];
        foreach ($transitions as $transition) {
            $transitionMeta = $model->wakaWorkflowGetTransitionMetadata($transition);
            $hidden = $transitionMeta['hidden'] ?? false;
            if (!$hidden) {
                $name = $transition->getName();
                $label = $transitionMeta['label'] ?? null;
                $button = $transitionMeta['button'] ?? null;
                $buton = $button ? $button : $label;
                $com = $transitionMeta['com'] ?? null;
                $redirect = $transitionMeta['redirect'] ?? null;
                $icon = $transitionMeta['icon'] ?? null;
                $color = $transitionMeta['color'] ?? null;
                $object = [
                    'value' => $name,
                    'label' => \Lang::get($buton),
                    'com' => $com,
                    'icon' => $icon,
                    'color' => $color,
                    'redirect' => $redirect,
                ];
                array_push($objTransition, $object);
            }
        }
        return $objTransition;
    }
}
