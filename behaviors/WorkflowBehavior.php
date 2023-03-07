<?php namespace Waka\Utils\Behaviors;

use Backend\Classes\ControllerBehavior;
use Redirect;
use Backend;
use Winter\Storm\Router\Helper as RouterHelper;
use Session;

class WorkflowBehavior extends ControllerBehavior
{
    public $controller;
    // public $workflowWidget;
    public $model;
    public $config;
    public $user;
    /**
     * @inheritDoc
     */
    // protected $requiredProperties = ['workflowConfig'];

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     */
    // protected $requiredConfig = ['places'];

    public $popupAfterSave;

    /**
     * @inheritDoc
     */

    //protected $workflowWidget;

    public function __construct($controller)
    {
        parent::__construct($controller);
        // $this->workflowWidget = new \Waka\Utils\Widgets\Workflow($controller);
        // $this->workflowWidget->alias = 'workflow';
        $this->controller = $controller;
        $this->user = $controller->user;
        //trace_log($controller->user);
        // $this->workflowWidget->model = $controller->formGetModel();
        // $this->workflowWidget->config = $this->config = $this->makeConfig($controller->workflowConfig, $this->requiredConfig);
        // $this->workflowWidget->bindToController();

        \Event::listen('waka.workflow.popup_afterSave', function($data) {
            \Session::put('popup_afterSave', $data);
        });
        $wfPopupAfterSave = \Session::get('popup_afterSave');
        if($wfPopupAfterSave) {
            $this->addJs('/plugins/waka/utils/assets/js/popup_after.js');
        }
    }

    public function listInjectRowClass($record, $value)
    {
        if ($record->hasNoRole()) {
            return 'nolink  disabled';
        }
    }

    public function formExtendFields($form)
    {
        $model = $form->model;
        if($model) {
            $fieldsToHide = $model->getWfHiddenFields();
            foreach($fieldsToHide as $field) {
                $form->removeField($field);
            }
        }
    }
    

    public function formBeforeSave($model)
    {
        //trace_log("formBeforeSave");
        //trace_log(post());
        if (post('change_state') != '') {
            $model->change_state = post('change_state');
        }
        if (post('try') != '') {
            //trace_log('Il y a un try : '.post('try'));
            $model->change_state = post('try');
            //IMPORTANT A GARDER POUR L INSTANT IL Y A DES FORMULES COMPLEXES A GARDER POUR UNE AUTRE FOIS-----------
            // $tryToChangeStates = post('try');
            // $wfMetadataStore = $model->getWakaWorkflow()->getMetadataStore();
            // $tryToChangeStates = explode(',',$tryToChangeStates);
            // $transitionChosen = null;
            // $modelData = $this->controller->formGetWidget()->getSaveData();
            // foreach($tryToChangeStates as $try) {
            //     //trace_log($try.'---------------------------');
            //     $transition = $model::getWfTransition($try, $model);
            //     $transitionMetaData = $wfMetadataStore->getTransitionMetadata($transition);
            //     $rulesSet = $transitionMetaData['rulesSet'] ?? null;
            //     $rules = $model->getWfRules($rulesSet);
            //     $error = 0;
            //     foreach($rules['fields'] as $key=>$rule) {
            //         //trace_log("test on key : ".$key);
            //         if(!$modelData[$key]) {
            //             //trace_log('error on'.$key);
            //             $error++;
            //         }
            //     }
            //     if(!$error) {
            //         //trace_log("try ok : ".$try);
            //         $model->change_state = $try;
            //         \Session::put('wf_redirect', $transitionMetaData['redirect']);
            //         break;
            //     }
            // }
            //trace_log("fin des tests");
            //trace_log($model->change_state);
        }
    }
        

    public function update_onSave($recordId = null, $context = null)
    {
        //trace_log("update_onSave---------------------");
        if(post('try')) {
            $this->controller->asExtension('FormController')->update_onSave($recordId, $context);
            $redirect = \Session::pull('wf_redirect');
            $model = $this->controller->formFindModelObject($recordId);
            //trace_log("REDIRECTION : ".$redirect);
            if($redirect == "refresh:1" || !$redirect) {
                    return Redirect::refresh();
                }
                $redirectUrl = null;
                if ($redirect == "close:1") {
                    $redirectUrl = $this->controller->formGetRedirectUrl('update-close', $model);
                }

                if ($redirect == "redirect:1") {
                    $redirectUrl = $this->controller->formGetRedirectUrl($context, $model);
                }
                //trace_log($redirectUrl);
                return Backend::redirect($redirectUrl);
        } else {
            return $this->controller->asExtension('FormController')->update_onSave($recordId, $context);

        }
    }
}
