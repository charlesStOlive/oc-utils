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

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'output',
            'type',
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
        return $this->makePartial('workflow');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->initWorkflow();

        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getWorkflowPlaceName();
        $this->vars['model'] = $this->model;
        $this->formField->options = $this->getWorkFlowOptions();
        $this->vars['field'] = $this->formField;
        //$this->vars['options'] = $this->getWorkFlowData();
        //$this->vars['datas'] = $this->getWorkFlowData();

    }

    // /**
    //  * @inheritDoc
    //  */
    // public function getWorkFlowData()
    // {
    //     $workflowName = $this->workflowName;
    //     $workflow = $this->model->workflow_get();
    //     $transitions = $workflow->getEnabledTransitions($this);

    //     $possibleTransition = [];
    //     foreach ($transitions as $transition) {
    //         $name = $transition->getName();
    //         $label = $workflow->getMetadata('label', $transition) ?? $name;
    //         $possibleTransition[$name] = $label;
    //     }
    // }

    public function initWorkflow()
    {
        $this->workflow = $workflow = $this->model->workflow_get();
        $this->workflowMetadata = $workflow->getMetadataStore();
    }

    /**
     * @inheritDoc
     */
    public function getWorkflowPlaceName()
    {
        $place = $this->model->state;
        //trace_log($place);
        $label = $this->workflow->getMetadataStore()->getPlaceMetadata($place)['label']; // string place name
        return \Lang::get($label);
    }

    public function getWorkFlowOptions()
    {
        $transitions = $this->workflow->getEnabledTransitions($this->model);
        $possibleTransition = [];
        foreach ($transitions as $transition) {
            $name = $transition->getName();
            $label = $this->workflowMetadata->getMetadata('label', $transition) ?? $name;
            $possibleTransition[$name] = \Lang::get($label);
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
        return $value;
    }

}
