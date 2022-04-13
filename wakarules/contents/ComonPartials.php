<?php namespace Waka\Utils\WakaRules\Contents;

use Waka\Utils\Classes\Rules\RuleContentBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use Waka\Utils\Interfaces\RuleContent as RuleContentInterface;

class ComonPartials extends RuleContentBase implements RuleContentInterface
{
    /**
     * Returns information about this event, including name and description.
     */
    public function subFormDetails()
    {
        return [
            'name'        => 'Un partial',
            'description' => 'Un partial du thème actif',
            'icon'        => 'icon-notilac',
            'premission'  => 'wcli.utils.cond.edit.admin',
        ];
    }

    public function listViews() {
        $views['partial'] = "Partial du site";
        return $views;
    }

    public function getText()
    {
        //trace_log('getText HTMLASK---');
        $hostObj = $this->host;
        //trace_log($hostObj->config_data);
        $partial = $hostObj->config_data['partial'] ?? null;
        if($partial) {
            return $partial;
        }
        return parent::getText();

    }

    /**
     * IS true
     */

    public function resolve($ds = []) {
        //ici on recupère les configs et le champs json datas...
        return [];
    }

    
    

    public function makeView($view = null, $ds = []) {
        return null;
    }
}
