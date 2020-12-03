<?php namespace Waka\Utils\Classes\Traits;

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
            $model->morphMany['state_logs'] = [
                'Waka\Utils\Models\StateLog',
                'name' => 'state_logeable',
                'table' => 'waka_utils_state_logeable',
            ];

            $model->bindEvent('model.beforeValidate', function () use ($model) {
                $changeState = $model->change_state;
                //trace_log("change_state : " . $changeState);
                if ($changeState) {
                    $transition = self::getTransitionobject($changeState, $model);
                    $rulesSet = $model->workflow_get()->getMetadataStore()->getTransitionMetadata($transition)['rulesSet'] ?? null;
                    $rules = $model->getWorkgflowRules($rulesSet);
                    if ($rules) {
                        $validation = \Validator::make($model->toArray(), $rules);
                        if ($validation->fails()) {
                            //trace_log($validation->messages());
                            throw new \ValidationException(['state_change' => "Impossible de changer d'état, verifiez les champs suivants : " . implode(", ", array_keys($rules))]);
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

}
