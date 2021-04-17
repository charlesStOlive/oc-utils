<?php namespace Waka\Utils\Classes\Traits;

use Lang;
use \Waka\Informer\Models\Inform;
use Session;

trait WakaWorkflowTrait
{
    use \ZeroDaHero\LaravelWorkflow\Traits\WorkflowTrait;

    /*
     * Constructor
     */
    public static function bootWorkflowTrait()
    {
        static::extend(function ($model) {
            /*
             * Define relationships
             */

            array_push($model->appends, 'wfPlaceLabel');
            array_push($model->purgeable, 'change_state');
            array_push($model->purgeable, 'state_close');

            $model->morphMany['state_logs'] = [
                'Waka\Utils\Models\StateLog',
                'name' => 'state_logeable',
                'table' => 'waka_utils_state_logeable',
            ];

            $model->bindEvent('model.beforeDelete', function () use ($model) {
                $model->state_logs()->delete();
            });
            // $model->bindEvent('model.beforeSave', function () use ($model) {
            //    $tryToChangeStates = post('try');
            //    $sessionKey = post('session_key');
            //    if(!$tryToChangeStates) {
            //        return;
            //    } else {
            //         $wfMetadataStore = $model->workflow_get()->getMetadataStore();
            //         $tryToChangeStates = explode(',',$tryToChangeStates);
            //         $transitionChosen = null;
            //         foreach($tryToChangeStates as $try) {
            //             //trace_log($try.'---------------------------');
            //             $transition = $model::getTransitionobject($try, $model);
            //             $transitionMetaData = $wfMetadataStore->getTransitionMetadata($transition);
            //             $rulesSet = $transitionMetaData['rulesSet'] ?? null;
            //             $rules = $model->getWorkgflowRules($rulesSet);
            //             $error = 0;
            //             foreach($rules['fields'] as $key=>$rule) {
            //                 trace_log("test on key : ".$key);
            //                 if(!$modelData[$key]) {
            //                     trace_log('error on'.$key);
            //                     $error++;
            //                 }
            //             }
            //             if(!$error) {
            //                 trace_log('ok pour : '.$try);
            //                 // $model->workflow_get()->apply($model, $try);
            //                 // \Session::put('wf_redirect', $transitionMetaData['redirect']);
            //                 break;
            //             }
            //         }
            //    }
            // });


            $model->bindEvent('model.beforeValidate', function () use ($model) {
                //trace_log('beforeValidate');
                $changeState = $model->change_state;
                $wf_try = strpos($changeState, ',');
                if ($wf_try && $changeState) {
                    //Si on test un changement de transition
                    $tryToChangeStates = explode(',',$changeState);
                    foreach($tryToChangeStates as $try) {
                        $transition = self::getTransitionobject($try, $model);
                        trace_log($transition);
                        $rulesSet = $model->workflow_get()->getMetadataStore()->getTransitionMetadata($transition)['rulesSet'] ?? null;
                        $rules = $model->getWorkgflowRules($rulesSet);
                        foreach($rules['fields'] as $key=>$rule) {
                            if(!$modelData[$key]) {
                                trace_log('error on'.$key);
                                $error++;
                            }
                        }
                        if(!$error) {
                            trace_log("try ok : ".$try);
                            // $model->workflow_get()->apply($model, $changeState);
                            // \Session::put('wf_redirect', $transitionMetaData['redirect']);
                            break;
                        }
                    }
                }
                if (!$wf_try && $changeState) {
                    //la transition et déjà choisi nous allons verifier. 
                    $transition = self::getTransitionobject($changeState, $model);
                    $rulesSet = $model->workflow_get()->getMetadataStore()->getTransitionMetadata($transition)['rulesSet'] ?? null;
                    $rules = $model->getWorkgflowRules($rulesSet);
                    if ($rules['fields'] ?? false) {
                        foreach($rules['fields'] as $key=>$rule) {
                            $model->rules[$key] = $rule;
                        }
                    }
                    //trace_log($model->toArray());
                    $model->workflow_get()->apply($model, $changeState);
                }
            });
            
            $model->bindEvent('model.afterSave', function () use ($model) {
                $changeState = $model->getOriginalPurgeValue('change_state');
                if ($changeState) {
                    //Preparation de l'evenement
                    $workflowName = $model->workflow_get()->getName();
                    $transition = self::getTransitionobject($changeState, $model);
                    $afterSaveFunction = $model->workflow_get()->getMetadataStore()->getTransitionMetadata($transition)['fncs'] ?? null;
                    
                    if ($afterSaveFunction) {
                        $afterSaveFunction = new \October\Rain\Support\Collection($afterSaveFunction);
                        $fnc = $afterSaveFunction->where('type', 'prod')->toArray();
                        \Event::fire('workflow.' . $workflowName . '.afterModelSaved', [$model, $fnc]);
                    }
                    //fin de la sauvegarde evenement
                    if (!$model->noStateSave) {
                        $state = new \Waka\Utils\Models\StateLog(['name' => $changeState]);
                        $model->state_logs()->add($state);
                    }
                }
            });
        });
    }

    public function getWfPlaces() {
        $workflow = $this->workflow_get();
        return $workflow->getDefinition()->getPlaces();
    }

    public function getStateAttribute($value) {
        if(!$value) {
            $workflow = $this->workflow_get();
            $places = $workflow->getDefinition()->getPlaces();
            $value = array_key_first($places);
        }
        return $value;
    }

    public static function getTransitionobject($changeState, $model)
    {
        $transitions = $model->workflow_get()->getDefinition()->getTransitions();
        foreach ($transitions as $transition) {
            if ($transition->getName() == $changeState) {
                return $transition;
                break;
            }
        }
    }

    public static function getManualTransitionobject($changeState, $model)
    {
        $transitions = $model->workflow_get()->getDefinition()->getTransitions();
        foreach ($transitions as $transition) {
            if ($transition->getName() == $changeState) {
                return $transition;
                break;
            }
        }
    }

    public function listAllWorklowstate()
    {
        $workflow = $this->workflow_get();
        $places = $workflow->getDefinition()->getPlaces();
        $results = [];
        foreach ($places as $place) {
            $name = $workflow->getMetadataStore()->getPlaceMetadata($place)['label'];
            $results[$place] = \Lang::get($name);
        }
        trace_log($results);
        return $results;
    }
    

    public function listWfPlaceFormAuto()
    {
        $workflow = $this->workflow_get();
        $place = $this->state;
        $form_auto = $workflow->getMetadataStore()->getPlaceMetadata($place)['form_auto'] ?? [];
        return $form_auto;
    }
    public function listWfPlaceCronAuto()
    {
        $workflow = $this->workflow_get();
        $place = $this->state;
        $form_auto = $workflow->getMetadataStore()->getPlaceMetadata($place)['cron_auto'] ?? [];
        return $form_auto;
    }

    public function listAllWorklowstateWithAutomatisation()
    {
        $workflow = $this->workflow_get();
        $places = $workflow->getDefinition()->getPlaces();
        $results = [];
        foreach ($places as $place) {
            $automatisation = $workflow->getMetadataStore()->getPlaceMetadata($place)['cron_auto'] ?? false;
            if ($automatisation) {
                $results[$place] = $automatisation;
            }
        }
        return $results;
    }

    public function getWorkgflowRules($rulesSet)
    {
        $rulesSets = $this->workflow_get()->getMetadataStore()->getWorkflowMetadata()['rulesSets'] ?? null;
        if (!$rulesSets) {
            return null;
        }
        $default = $rulesSets['default'];
        if ($default && !$rulesSet) {
            return $default;
        }
        return $rulesSets[$rulesSet] ?? null;
    }

    public function hasNoRole() {
        $user = \BackendAuth::getUser();
        $place = null;
        $place = $this->state;
        if (!$place) {
            $workflow = $this->workflow_get();
            $places = $workflow->getDefinition()->getPlaces();
            $place = array_key_first($places);
        }
        //trace_log($place);

        $noRoleCode = $this->workflow_get()->getMetadataStore()->getPlaceMetadata($place)['norole'] ?? []; // string place name
        //trace_log($noRoleCode);
        //trace_log($user->role->code);
        
        if(in_array($user->role->code, $noRoleCode))  {
            return true;
        } else {
            return false;
        }
    }

    public function getWfPlaceLabelAttribute($state_column = null)
    {
        //A faire $state_column pour changer la colonne source de l'etat
        $place = null;
        if ($state_column) {
            $place = $this->{$state_column};
        } else {
            $place = $this->state;
        }
        //trace_log($place);
        $label = $this->workflow_get()->getMetadataStore()->getPlaceMetadata($place)['label'] ?? $place; // string place name
        return Lang::get($label);
    }

    // public function getWfAutomatisation()
    // {
    //     $place = $this->model->state;

    //     if (!$place) {
    //         $arrayPlaces = $this->workflow->getMarking($this->model)->getPlaces();
    //         $place = array_key_first($arrayPlaces);
    //     }
    //     $automatisation = $this->workflow->getMetadataStore()->getPlaceMetadata($place)['automatisation'] ?? false;
    //     if ($automatisation) {

    //     }
    //     return \Lang::get($label);
    // }
}
