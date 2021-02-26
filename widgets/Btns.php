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

    public function prepareComonVars()
    {
        $this->vars['modelClass'] = str_replace('\\', '\\\\', $this->config->modelClass);
        $this->vars['user'] = \BackendAuth::getUser();
        $this->vars['hasWorkflow'] = $this->config->workflow;
    }

    public function renderBar($mode = 'update', $modelId = null)
    {
        $this->prepareComonVars();
        $configBtns = $this->config->action_bar['config_btns'] ?? null;
        $this->vars['mode'] = $mode;
        $this->vars['partials'] = $this->config->action_bar['partials'] ?? null;
        if ($mode == 'update') {
            $model = $this->controller->formGetModel();
            $this->vars['btns'] = $this->getBtns($configBtns);
            $this->vars['modelId'] = $model->id;
            return $this->makePartial('action_bar');
        } else {
            $this->vars['btns'] = $this->getBtns($configBtns);
            $this->vars['modelId'] = $modelId;
            return $this->makePartial('container_bar');
        }

    }

    public function renderWorkflowOrBtn()
    {
        $this->prepareComonVars();
        $hasWorkflow = $this->config->workflow;
        if ($hasWorkflow) {
            $this->vars['transitions'] = $this->getWorkFlowTransitions();
            return $this->makePartial('sub/workflow_button');
        } else {
            return $this->makePartial('sub/base_buttons');
        }

    }

    public function renderBreadcrump()
    {
        $this->prepareComonVars();
        if ($this->config->breadcrump) {
            $this->vars['breadcrump'] = $this->config->breadcrump;
            return $this->makePartial('breadcrump');
        } else {
            return '';
        }
    }

    public function renderToolBar($secondaryLabel = false)
    {
        $this->prepareComonVars();
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
        $this->vars['createBtn'] = $toolBar['create']['show'] ?? false;
        $this->vars['createBtnLabel'] = $toolBar['create']['label'] ?? 'Créer';
        $this->vars['createBtnUrl'] = $toolBar['create']['url'] ?? '#';
        $this->vars['reorderUrl'] = $toolBar['reorder']['url'] ?? false;
        $this->vars['DeleteBtn'] = $toolBar['delete']['show'] ?? false;
        $this->vars['reorder'] = $toolBar['reorder']['show'] ?? false;
        $this->vars['reorderUrl'] = $toolBar['reorder']['url'] ?? false;
        $this->vars['isLot'] = true;
        $this->vars['hasLot'] = $toolBar['config_lot'] ?? false;
        $this->vars['partials'] = $toolBar['partials'] ?? null;
        $this->vars['btns'] = $this->getBtns($toolBar['config_btns'] ?? null);
        return $this->makePartial('tool_bar');

    }

    public function renderLot()
    {
        $this->prepareComonVars();
        $configBtns = $this->config->tool_bar['lot'] ?? null;
        $this->vars['hasWorkflow'] = $this->config->workflow;
        $this->vars['btns'] = $this->getBtns($this->config->tool_bar['config_lot'] ?? null);
        return $this->makePartial('container_lot');
    }

    public function getBtns($configurator)
    {
        if (!$configurator) {
            return null;
        }
        $btns = [];
        $groups = $configurator['groups'] ?? [];
        $collection = new Collection($configurator['btns']);
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
        } else if ($format == 'grouped') {
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
                $object = [
                    'value' => $name,
                    'label' => \Lang::get($label),
                ];
                array_push($objTransition, $object);
            }
        }
        return $objTransition;
    }

}
