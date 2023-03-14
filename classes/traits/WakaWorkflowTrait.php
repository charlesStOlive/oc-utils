<?php

namespace Waka\Utils\Classes\Traits;

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

            array_push($model->attributesToDs, 'wfPlaceLabel');
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

            $model->bindEvent('model.beforeValidate', function () use ($model) {
                //trace_log('beforeValidate');
                //trace_log($model->name);
                //Recuperation de l'information change_state
                $changeState = $model->change_state;
                //trace_log('beforeValidate : ' .$model->change_state);
                //on verifie s'il y a plusieurs transition possible
                $wf_try = strpos($changeState, ',');
                if ($wf_try && $changeState) {
                    //trace_log("On test un changement de transition");
                    //Si on test un changement de transition
                    $tryToChangeStates = explode(',', $changeState);
                    //trace_log($tryToChangeStates);
                    $wfMetadataStore = $model->getWakaWorkflow()->getMetadataStore();
                    $trySuccess = null;
                    foreach ($tryToChangeStates as $try) {
                        //trace_log("-----try : ".$try." sur état : ".$model->state);
                        //trace_log("Etats possible : ") ;

                        if (!$model->wakaWorkflowCan($try)) {
                            //Si la transition n'est pas compatible au saute cette boucle.
                            //trace_log("Transition incompatible : ".$try);
                            continue;
                        }
                        $transition = self::getWfTransition($try, $model);
                        $transitionMetaData = $wfMetadataStore->getTransitionMetadata($transition);
                        $rulesSet = $transitionMetaData['rulesSet'] ?? 'default';
                        $rules = $model->getWfRules($rulesSet);
                        $error = 0;
                        //trace_log($rules['fields'] ?? 'Pas de rules');
                        if (!$rules['fields'] ?? false) {
                            //trace_log("il n' y a pas de rules");
                            $trySuccess = $model->change_state = $try;
                            $model->getWakaWorkflow()->apply($model, $model->change_state);
                            return;
                        }
                        foreach ($rules['fields'] as $key => $rule) {
                            if (!$model[$key]) {
                                //trace_log('error on'.$key);
                                $error++;
                            }
                        }
                        if (!$error) {
                            //trace_log("try ok : ".$try);
                            $model->change_state = $try;
                            break;
                        }
                    }
                    if (!$trySuccess) {
                        if ($model->wfMustTrans) {
                            //throw new \ValidationException(['memo' => \Lang::get('waka.utils::lang.workflow.must_trans')]);
                        }
                        $model->change_state = null;
                    }
                } else if (!$wf_try && $changeState) {
                    //trace_log("On a un changement de transition");
                    //la transition et déjà choisi nous allons verifier. 
                    $transition = self::getWfTransition($changeState, $model);
                    //trace_log($transition);
                    $rulesSet = $model->wakaWorkflowGetTransitionMetadata($transition)['rulesSet'] ?? null;
                    $rules = $model->getWfRules($rulesSet);
                    if ($rules['fields'] ?? false) {
                        foreach ($rules['fields'] as $key => $rule) {
                            $model->rules[$key] = $rule;
                        }
                    }
                    //trace_log($model->toArray());
                    //$model->getWakaWorkflow()->apply($model, $changeState);
                } else if ($model->wfMustTrans) {
                    //throw new \ValidationException(['memo' => \Lang::get('waka.utils::lang.workflow.must_trans')]);
                }
            });
            $model->bindEvent('model.beforeSave', function () use ($model) {
                $changeState = $model->change_state ? $model->change_state  : $model->getOriginalPurgeValue('change_state');
                if (!$changeState) {
                    return;
                }
                //trace_log("beforeSave change State: ".$changeState);
                $transition = self::getWfTransition($changeState, $model);
                $rulesSet = $model->wakaWorkflowGetTransitionMetadata($transition)['rulesSet'] ?? null;
                $rules = $model->getWfRules($rulesSet);
                if ($rules['fields'] ?? false) {
                    foreach ($rules['fields'] as $key => $rule) {
                        $model->rules[$key] = $rule;
                    }
                }
                //trace_log($model->toArray());
                //TODO ICI PROBLEME DE MODIFICATION DE LETTRE CHANGE STATE IMPOSSIBLE
                $model->getWakaWorkflow()->apply($model, $changeState);
            });


            $model->bindEvent('model.afterSave', function () use ($model) {
                //trace_log('model after save as runned');
                $changeState = $model->change_state ? $model->change_state  : $model->getOriginalPurgeValue('change_state');
                $model->storeStatelog($changeState);
                $model->executeWorkflowFunctionAfterSave($changeState);
            });
        });
    }

    public function currentState()
    {
        return $this->morphOne(\Waka\Utils\Models\StateLog::class, 'state_logeable')->latestOfMany();
    }


    public function executeWorkflowFunctionAfterSave($changeState)
    {
        if (!$changeState) {
            return;
        }
        $workflowName =  $this->defaultWorkflowName;
        //
        $transition = self::getWfTransition($changeState, $this);
        $afterSaveFunction = $this->wakaWorkflowGetTransitionMetadata($transition)['fncs'] ?? null;
        if ($afterSaveFunction) {
            $afterSaveFunction = new \Winter\Storm\Support\Collection($afterSaveFunction);
            $fnc = $afterSaveFunction->where('type', 'prod')->toArray();
            \Event::fire('workflow.' . $workflowName . '.afterModelSaved', [$this, $fnc]);
        }
    }

    public function getWorkflowName($workflowName = null)
    {
        if (!$workflowName) {
            if ($this->actualWorkflow) {
                return $this->defaultWorkflowName = $this->actualWorkflow;
            } else {
                return $this->defaultWorkflowName;
            }
        } 
        return $this->defaultWorkflowName =  $workflowName;
    }

    public function getWakaWorkflow($workflowName = null)
    {
        $workflowName = $this->getWorkflowName($workflowName);
        return $this->workflow_get($workflowName);
    }

    public function wakaWorkflowCan($transition, $workflowName = null)
    {
        $workflowName = $this->getWorkflowName($workflowName);
        return $this->workflow_can($transition, $workflowName);
    }

    public function wakaWorkflowTransitions($workflowName = null) {
        $workflowName = $this->getWorkflowName($workflowName);
        return $this->workflow_transitions($workflowName);
    }

    public function wakaWorkflowGetTransitionMetadata($transition) {
        return $this->getWakaWorkflow()->getMetadataStore()->getTransitionMetadata($transition);
    }

    public function storeStatelog($changeState)
    {
        if (!$changeState) {
            return;
        }
        //Preparation de l'evenement
        $transition = self::getWfTransition($changeState, $this);


        //fin de la sauvegarde evenement
        if (!$this->noStateSave) {
            $user = \BackendAuth::getUser();
            if ($user) {
                $user = $user->fullName;
            } else {
                $user = 'App';
            }
            $state = new \Waka\Utils\Models\StateLog([
                'name' => $changeState,
                'state' => $transition->getTos()[0],
                'user' => $user,
            ]);
            $this->state_logs()->add($state);
        }
    }

    public function getWfPlaces()
    {
        $workflow = $this->getWakaWorkflow();
        return $workflow->getDefinition()->getPlaces();
    }

    public function getStateAttribute($value)
    {
        if (!$value) {
            $workflow = $this->getWakaWorkflow();
            $places = $workflow->getDefinition()->getPlaces();
            $value = array_key_first($places);
        }
        return $value;
    }

    public static function getWfTransition($changeState, $model)
    {
        $transitions = $model->getWakaWorkflow()->getDefinition()->getTransitions();
        foreach ($transitions as $transition) {
            if ($transition->getName() == $changeState) {
                return $transition;
                break;
            }
        }
    }

    public function getWfTransitionRedirection($label_transition = null)
    {
        $transition = self::getWfTransition($label_transition, $this);
        $redirection  = $this->wakaWorkflowGetTransitionMetadata($transition)['redirect'] ?? null;
        return $redirection; // string place name
    }

    public function listAllWorklowstate()
    {
        $workflow = $this->getWakaWorkflow();
        $places = $workflow->getDefinition()->getPlaces();
        $results = [];
        foreach ($places as $place) {
            $name = $workflow->getMetadataStore()->getPlaceMetadata($place)['label'];
            $results[$place] = \Lang::get($name);
        }
        //trace_log($results);
        return $results;
    }


    public function listWfPlaceFormAuto()
    {
        $workflow = $this->getWakaWorkflow();
        $place = $this->state;
        $form_auto = $workflow->getMetadataStore()->getPlaceMetadata($place)['form_auto'] ?? [];
        return $form_auto;
    }
    public function listWfPlaceCronAuto()
    {
        $workflow = $this->getWakaWorkflow();
        $place = $this->state;
        $form_auto = $workflow->getMetadataStore()->getPlaceMetadata($place)['cron_auto'] ?? [];
        return $form_auto;
    }

    public function listWfWorklowstateWithAutomatisation()
    {
        $workflow = $this->getWakaWorkflow();
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



    public function getWfRules($rulesSet)
    {
        $rulesSets = $this->getWakaWorkflow()->getMetadataStore()->getWorkflowMetadata()['rulesSets'] ?? null;
        if (!$rulesSets) {
            return null;
        }
        $default = $rulesSets['default'];
        if ($default && !$rulesSet) {
            return $default;
        }
        return $rulesSets[$rulesSet] ?? null;
    }

    public function hasNoRole()
    {
        $user = \BackendAuth::getUser();
        if ($user->is_superuser) {
            return false;
        }
        $place = null;
        $place = $this->state;
        if (!$place) {
            $workflow = $this->getWakaWorkflow();
            $places = $workflow->getDefinition()->getPlaces();
            $place = array_key_first($places);
        }

        //trace_log($place);

        $noRoleCode = $this->getWakaWorkflow()->getMetadataStore()->getPlaceMetadata($place)['norole'] ?? []; // string place name
        if ($noRoleCode == []) {
            return false;
        }


        if (in_array($user->role->code, $noRoleCode)) {
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
        $label = $this->getWakaWorkflow()->getMetadataStore()->getPlaceMetadata($place)['label'] ?? $place; // string place name
        return Lang::get($label);
    }



    public function getWfMustTransAttribute($place = null)
    {
        if (!$place) {
            $place = $this->state;
        }
        return $this->getWakaWorkflow()->getMetadataStore()->getPlaceMetadata($place)['must_trans'] ?? false; // string place name
    }

    public function getWfHiddenFields($place = null)
    {
        if (!$place) {
            $place = $this->state;
        }
        return $this->getWakaWorkflow()->getMetadataStore()->getPlaceMetadata($place)['hidden_fields'] ?? []; // string place name
    }
    //
    // public function getLastLog($stateName)
    // {
    //     $log =  $this->state_logs()->where('state', $stateName)->orderBy('created_at', 'desc')->first();
    //     if ($log) {
    //         return $log->created_at;
    //     } else {
    //         return null;
    //     }
    // }
}
