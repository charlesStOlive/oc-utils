<?php

namespace Waka\Utils\Behaviors;

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
        $this->controller = $controller;
        $this->user = $controller->user;
        \Event::listen('waka.workflow.popup_afterSave', function ($data) {
            \Session::put('popup_afterSave', $data);
        });
        $wfPopupAfterSave = \Session::get('popup_afterSave');
        if ($wfPopupAfterSave) {
            $this->addJs('/plugins/waka/utils/assets/js/popup_after.js');
        }
    }

    public function listInjectRowClass($record, $value)
    {
        if (!$record->userHasWfPermission()) {
            return 'nolink  disabled';
        }
    }

    public function relationExtendConfig($config, $field, $model)
    {
        //trace_log('relationExtendConfig');
        $fieldsReadOnly = $model->getWfROFields();
        if(!count($fieldsReadOnly)) {
            return;
        }
        //trace_log($fieldsReadOnly);
        if(in_array($field , $fieldsReadOnly)) {
            $config->view['toolbarButtons'] = false;
            $config->view['showCheckboxes'] = false;
            $config->manage = [];
        } 
        
    }

    public function formExtendFields($form)
    {
        $model = $form->model;
        if ($model) {
            $fieldsToHide = $model->getWfHiddenFields();
            foreach ($fieldsToHide as $field) {
                $form->removeField($field);
            }
            $fieldsReadOnly = $model->getWfROFields();
            foreach ($fieldsReadOnly as $field) {
                $roField = $form->getField($field);
                if($roField) {
                    $roField->readOnly = true;
                }  
            }
        }
    }


    public function formBeforeSave($model)
    {
        //trace_log("formBeforeSave");
        //trace_log(post());
        if (!$model->userHasWfPermission()) {
            throw new \ValidationException(['error' => "Vous n'avez pas le droit d'enregistrer dans l'état actuel"]);
        } else {
            if (post('change_state') != '') {
                $model->change_state = post('change_state');
            }
            if (post('try') != '') {
                //trace_log('Il y a un try : '.post('try'));
                $model->change_state = post('try');
            }
        }
    }


    public function update_onSave($recordId = null, $context = null)
    {
        //trace_log("update_onSave---------------------");
        if (post('try')) {
            $this->controller->asExtension('FormController')->update_onSave($recordId, $context);
            $redirect = \Session::pull('wf_redirect');
            $model = $this->controller->formFindModelObject($recordId);
            //trace_log("REDIRECTION : ".$redirect);
            if ($redirect == "refresh:1" || !$redirect) {
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
