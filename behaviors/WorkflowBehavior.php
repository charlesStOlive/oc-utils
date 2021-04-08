<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;
use Redirect;
use Backend;
use October\Rain\Router\Helper as RouterHelper;
use Session;

class WorkflowBehavior extends ControllerBehavior
{
    public $controller;
    public $workflowWidget;
    public $model;
    public $config;
    public $user;
    /**
     * @inheritDoc
     */
    protected $requiredProperties = ['workflowConfig'];

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     */
    protected $requiredConfig = ['places'];

    /**
     * @inheritDoc
     */

    //protected $workflowWidget;

    public function __construct($controller)
    {
        parent::__construct($controller);
        $this->workflowWidget = new \Waka\Utils\Widgets\Workflow($controller);
        $this->workflowWidget->alias = 'workflow';
        $this->controller = $controller;
        $this->user = $controller->user;
        //trace_log($controller->user);
        $this->workflowWidget->model = $controller->formGetModel();
        $this->workflowWidget->config = $this->config = $this->makeConfig($controller->workflowConfig, $this->requiredConfig);
        $this->workflowWidget->bindToController();
    }

    public function listInjectRowClass($record, $value)
    {
        if ($record->hasNoRole()) {
            return 'nolink  disabled';
        }
    }

    public function formBeforeSave($model)
    {
        if (post('change_state') != '') {
            $model->change_state = post('change_state');
        }
        if (post('try') != '') {
            $tryToChangeStates = post('try');
            $wfMetadataStore = $model->workflow_get()->getMetadataStore();
            $tryToChangeStates = explode(',',$tryToChangeStates);
            $transitionChosen = null;
            foreach($tryToChangeStates as $try) {
                $transition = $model::getTransitionobject($try, $model);
                $transitionMetaData = $wfMetadataStore->getTransitionMetadata($transition);
                $rulesSet = $transitionMetaData['rulesSet'] ?? null;
                $rules = $model->getWorkgflowRules($rulesSet);
                $error = 0;
                foreach($rules['fields'] as $key=>$rule) {
                    if(!$model[$key]) {
                        $error++;
                    }
                }
                if(!$error) {
                    $model->change_state = $try;
                    \Session::put('wf_redirect', $transitionMetaData['redirect']);
                    break;
                }
            }
        }
    }
        

    public function update_onSave($recordId = null, $context = null)
    {
        $redirect = \Session::pull('wf_redirect');
        trace_log($redirect);
        if($redirect) {
            $this->controller->asExtension('FormController')->update_onSave($recordId, $context);
            if($redirect == "refresh:1") {
                    $redirect =  Redirect::refresh();
                }
                $redirectUrl = null;
                if ($redirect == "close:1") {
                    $redirectUrl = $this->controller->formGetRedirectUrl('update-close', $model);
                }

                if ($redirect == "redirect:1") {
                    $redirectUrl = $this->controller->formGetRedirectUrl($context, $model);
                }
        } else {
            return $this->controller->asExtension('FormController')->update_onSave($recordId, $context);

        }
    }
}
