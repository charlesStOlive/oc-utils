<?php namespace Waka\Utils\Classes\Traits;

use Lang;
use \Waka\Informer\Models\Inform;

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

            $model->morphMany['state_logs'] = [
                'Waka\Utils\Models\StateLog',
                'name' => 'state_logeable',
                'table' => 'waka_utils_state_logeable',
            ];

            $model->bindEvent('model.beforeDelete', function () use ($model) {
                $model->state_logs()->delete();
            });

            $model->bindEvent('model.beforeValidate', function () use ($model) {
                $changeState = $model->change_state;
                //trace_log("change_state : " . $changeState);
                if ($changeState) {
                    $transition = self::getTransitionobject($changeState, $model);
                    $rulesSet = $model->workflow_get()->getMetadataStore()->getTransitionMetadata($transition)['rulesSet'] ?? null;
                    $rules = $model->getWorkgflowRules($rulesSet);
                    if ($rules['fields'] ?? false) {
                        //trace_log($rules);
                        $validation = \Validator::make($model->toArray(), $rules['fields'] ?? [], Lang::get($rules['messages']));
                        if ($validation->fails()) {
                            //trace_log($validation->messages());
                            throw new \ValidationException(['state_change' => $validation->messages()->first()]);
                        }
                    }
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

                    //$fnc = $afterSaveFunction->where('type', 'prod')->keys()->first();

                    //trace_log($fnc);
                    //trace_log($attributes);
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

    public function listAllWorklowstate()
    {
        $workflow = $this->workflow_get();
        $places = $workflow->getDefinition()->getPlaces();
        $results = [];
        foreach ($places as $place) {
            $name = $workflow->getMetadataStore()->getPlaceMetadata($place)['label'];
            $results[$place] = \Lang::get($name);
        }
        return $results;
    }

    public function listAllWorklowstateWithAutomatisation()
    {
        $workflow = $this->workflow_get();
        $places = $workflow->getDefinition()->getPlaces();
        $results = [];
        foreach ($places as $place) {
            $automatisation = $workflow->getMetadataStore()->getPlaceMetadata($place)['automatisations'] ?? false;
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
        if (!$place) {
            $workflow = $this->workflow_get();
            $places = $workflow->getDefinition()->getPlaces();
            $place = array_key_first($places);
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
