<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * workflow Form Widget
 */
class Workflow extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_utils_workflow';

    public $output = "enabled";
    public $type = "transforms";
    public $stateFrom = "state";
    public $workflow;
    public $workflowMetadata;
    public $mode = "edit-dd";

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'output',
            'mode',
            'workflowName',
            'stateFrom',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        if($this->mode == "read") {
            return $this->makePartial('read');
        } elseif($this->mode == "edit-list") {
            return $this->makePartial('workflow_list');
        } else {
            return $this->makePartial('workflow_dd');
        }
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->initWorkflow();

        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getWorkflowPlaceNameCom();
        $this->vars['com'] = $this->getWorkflowPlaceNameCom('com');
        $this->vars['icon'] = $this->getWorkflowPlaceNameCom('icon');
        $this->vars['model'] = $this->model;
        $this->formField->options = $this->getWorkFlowOptions();
        $this->vars['field'] = $this->formField;
        //trace_log("No role : ".$this->model->hasNoRole());
        $this->formField->readOnly = $this->model->hasNoRole();
        $this->vars['noRole'] = $this->model->hasNoRole();
        //$this->vars['options'] = $this->getWorkFlowData();
        //$this->vars['datas'] = $this->getWorkFlowData();
    }

    

    public function initWorkflow()
    {
        $this->workflow = $workflow = $this->model->getWakaWorkflow();
        $this->workflowMetadata = $workflow->getMetadataStore();
    }

    /**
     * @inheritDoc
     */
    public function getWorkflowPlaceNameCom($type = 'label')
    {
        $place = $this->model->state;
        if (!$place) {
            $arrayPlaces = $this->workflow->getMarking($this->model)->getPlaces();
            $place = array_key_first($arrayPlaces);
        }
        $label = $this->workflow->getMetadataStore()->getPlaceMetadata($place)[$type] ?? null; // string place name
        return \Lang::get($label);
    }

    public function getWorkFlowOptions()
    {
        $transitions = $this->workflow->getEnabledTransitions($this->model);
        $possibleTransition = [];
        foreach ($transitions as $transition) {
            $hidden = $this->workflowMetadata->getMetadata('hidden', $transition) ?? false;
            if (!$hidden) {
                $name = $transition->getName();
                $label = $this->workflowMetadata->getMetadata('label', $transition) ?? $name;
                $possibleTransition[$name] = [
                    'label' => $label,
                    'type' => $this->workflowMetadata->getMetadata('type', $transition) ?? null,
                    'com' => $this->workflowMetadata->getMetadata('com', $transition) ?? null,
                    'icon' => $this->workflowMetadata->getMetadata('icon', $transition) ?? null,
                ];
            }
        }
        return $possibleTransition;
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->addCss('css/workflow.css', 'waka.utils');
        $this->addJs('js/workflow.js', 'waka.utils');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        if($this->mode == "read") {
            return \Backend\Classes\FormField::NO_SAVE_DATA;
        } else {
            return $value;
        }
        
    }
}
