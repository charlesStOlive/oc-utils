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
        $this->vars['value'] = $this->getWorkflowPlaceNameCom();
        $this->vars['com'] = $this->getWorkflowPlaceNameCom('com');
        $this->vars['model'] = $this->model;
        $this->formField->options = $this->getWorkFlowOptions();
        $this->vars['field'] = $this->formField;
        $this->formField->readOnly = $this->getWorkflowNoRole();
        $this->vars['noRole'] = $this->getWorkflowNoRole();
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

    public function getWorkflowNoRole()
    {
        $place = $this->model->state;
        if (!$place) {
            $arrayPlaces = $this->workflow->getMarking($this->model)->getPlaces();
            $place = array_key_first($arrayPlaces);
        }
        $noRoles = $this->workflow->getMetadataStore()->getPlaceMetadata($place)['norole'] ?? null; // string place name
        $user = \BackendAuth::getUser();
        if ($noRoles) {
            if (in_array($user->role->code, $noRoles)) {
                return true;
            }
        }
        return false;
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
                $possibleTransition[$name] = \Lang::get($label);
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
        return $value;
    }
}
