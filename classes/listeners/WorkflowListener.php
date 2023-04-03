<?php

namespace Waka\Utils\Classes\Listeners;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*

class WorkflowListener
{
    /**
     * Handle workflow guard events.
     */

    /**
     * $transitionName = $event->getTransition()->getName();
     * $eventTransition = $event->getTransition();
     * $from = $eventTransition->getFroms();
     * $to = $eventTransition->getTos();
     * $fnc = $event->getMetadata('fnc', $eventTransition);
     * $places = implode(', ', array_keys($event->getMarking()->getPlaces()));
     * $globalMetadata = $event->getWorkflow()->getMetadataStore()->getMetadata('name');
     * $model = $event->getSubject();
     * trace_log($transitionName);
     * trace_log($eventTransition);
     * trace_log($from);
     * trace_log($to);
     * trace_log($fnc);
     * trace_log($places);
     * trace_log($globalMetadata);
     * trace_log($model->name);
     */
    public function recLogs($event) {
        //trace_log("recLogs");
        $model = $event->getSubject();
        //trace_log($model->name);
        $model->storeStatelog($event);
        return false;
    }
    public function onGuard($event)
    {
        $blocked = $this->launchGardFunction($event);
        $event->setBlocked($blocked);
    }

    /**
     * Handle workflow leave event.
     */
    public function onLeave($event)
    {
        $this->launchFunction($event, 'leave');
    }

    /**
     * Handle workflow transition event.
     */
    public function onTransition($event)
    {
        $this->launchFunction($event, 'transition');
    }

    /**
     * Handle workflow enter event.
     */
    public function onEnter($event)
    {
        $this->launchFunction($event, 'enter');
    }

    /**
     * Handle workflow entered event.
     */
    public function onEntered($event)
    {
        $this->launchFunction($event, 'entered');
    }

    /**
     * FONCTIONS DE PRODUCTION UNIQUEMENT LANCE APRES SAVE LE MODEL
     */

    /**
     * Handle workflow onAfterSavedFunction.
     * ATTention differents des autres evenements, provien du workflowtrait
     * Obligatoire à cause du systhème de dataSource qui ne fonctionne que sur les élements déjà enregistré.
     */
    public function onAfterSavedFunction($model, $fnc)
    {
        $functionName = array_keys($fnc)[0] ?? null;
        if (!$functionName) {
            return;
        }

        $arguments = $fnc[$functionName]['args'] ?? null;
        if (method_exists($this, $functionName)) {
            $this->{$functionName}($model, $arguments ?? null);
        }
    }

    public function launchFunction($event, $type = 'trait')
    {
        $eventTransition = $event->getTransition();
        $fncs = new \Winter\Storm\Support\Collection($event->getMetadata('fncs', $eventTransition));
        if ($type) {
            $fncs = $fncs->where('type', $type);
        }
        if (!$fncs) {
            return;
        }
        foreach ($fncs->toArray() as $fnc => $attributes) {
            if (method_exists($this, (string) $fnc)) {
                $this->{$fnc}($event, $attributes['args'] ?? null);
            } else {
                throw new \SystemException("la fonction : " . $fnc . " n'existe pas dans l'ecouteur d'evenement du workflow : ".get_class($this));
            }
        }
    }

    public function launchGardFunction($event)
    {
        $eventTransition = $event->getTransition();
        $fncs = new \Winter\Storm\Support\Collection($event->getMetadata('fncs', $eventTransition));

        $fncs = $fncs->where('type', 'gard');
        if (!$fncs->count()) {
            return false;
        }
        foreach ($fncs->toArray() as $fnc => $attributes) {
            if (method_exists($this, (string) $fnc)) {
                return $this->{$fnc}($event, $attributes['args'] ?? null);
            } else {
                throw new \SystemException("la fonction : " . $fnc . " n'existe pas dans l'ecouteur d'evenement du workflow");
            }
        }
    }
}
