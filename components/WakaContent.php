<?php namespace Waka\Utils\Components;

use Cms\Classes\ComponentBase;

class WakaContent extends ComponentBase
{
    private $dsCode;
    private $slug;


    public function componentDetails()
    {
        return [
            'name'        => 'content Component',
            'description' => 'Affiche les contenus des rules content'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function init() {
            // trace_log("on Init-------------------");
            // trace_log($this->property('slug'));
            // trace_log($this->property('productor'));
    }

    public function prepareVars() {

    }

    public function onRender()
    {
        $this->dsCode = $this->property('ds');
        $this->slug = $this->property('slug');

    }

    public function datas() {
        $placement = $this->property('placement');
        trace_log("placement : ".$placement);
        $content = [];
        $ds = \DataSources::find($this->dsCode);
        $ruleContents = $ds->class::where('slug', $this->slug )->first()->rule_contents();
        foreach($ruleContents->get() as $ruleContent) {
            if($ruleContent->placement != $placement or !$ruleContent->view) {
                //Le ruleContent n'est pas dans le placement souhaité on ne le traite pas. 
                //Si la vue est oublié on l'enlève
                continue;
            }
            if(!$ruleContent->view) {
                //Il manque le nom de la vue on ne le gère pas. 
                continue;
            }
            if($ruleContent->view == 'partial') {
                //Le comportement spécial PARTIAL le ruleContent retourne uniquement le nom du partial du site. 
                $content[$ruleContent->code] = $this->renderPartial($ruleContent->resolve());
            } else if($ruleContent->view != 'code') {
                //Le comportement classique le ruleContent genère la vue
                $content[$ruleContent->code] = $ruleContent->makeView()->render();
            }
        }
        return $content;
    }
}
