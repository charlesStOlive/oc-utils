<?php namespace Waka\Utils\Widgets;

use Backend\Classes\WidgetBase;

class Workflow extends WidgetBase
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'workflow';

    public $config;
    public $model;
    public $fields;

    public function render()
    {
        $this->prepareVars();
        $this->vars['modelId'] = $this->model->id;
        $transitions = $this->getWorkFlowTransitions();
        // if (count($transitions)) {
        //     $this->vars['transitions'] = $this->getWorkFlowTransitions();
        //     return $this->makePartial('lists_button');
        // } else {
        //     return $this->makePartial('buttons');
        // }
        $this->vars['transitions'] = $this->getWorkFlowTransitions();
        return $this->makePartial('lists_button');
    }

    public function prepareVars()
    {
        $this->model = $this->controller->formGetModel();
    }

    public function loadAssets()
    {
        //$this->addCss('css/sidebarinfo.css', 'Waka.Utils');
    }

    public function getWorkFlowTransitions($withHidden = false)
    {
        $transitions = $this->model->getWakaWorkflow()->getEnabledTransitions($this->model);
        $workflowMetadata = $this->model->getWakaWorkflow()->getMetadataStore();
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
