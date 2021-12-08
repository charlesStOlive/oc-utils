<?php namespace Waka\Utils\WakaRules\Asks;

use Waka\Utils\Classes\Rules\AskBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;

class Content extends AskBase
{
    use \Waka\Utils\Classes\Traits\StringRelation;


    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function askDetails()
    {
        return [
            'name'        => 'Contenu',
            'description' => 'Choisissez un bloc de contenu',
            'icon'        => 'icon-html5',
            'premission'  => 'wcli.utils.ask.edit.admin',
            'ask_emit'    => 'richeditor',
            'show_attributes' => true,
            'word_type' => 'HTM',
        ];
    }

    public function getText()
    {
        //trace_log('getText HTMLASK---');
        $hostObj = $this->host;
        //trace_log($hostObj->config_data);
        $relation = $hostObj->config_data['relation'] ?? 'Aucune';
        $code = $hostObj->config_data['contentCode'] ?? null;
        return "Code de contenu : ". $code . " |  relation  ".$relation;
    }
    /**
     * $modelSrc le Model cible
     * $context le type de contenu twig ou word
     * $dataForTwig un modèle en array fournit par le datasource ( avec ces relations parents ) 
     */

    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {
        $relation = $this->getConfig('relation');
        $contentCode = $this->getConfig('contentCode');
        if($relation) {
            $modelSrc =  $this->getStringModelRelation($modelSrc, $relation);
        }
        if($modelSrc->rule_contents) {
            $content = $modelSrc->rule_contents->where('code', $contentCode)->first()->toArray();
            return  $content;
        } else {
            \Log::error('Le trait \Waka\Utils\Classes\Traits\WakaContent n existe pas');
            return null;
        }
    }
}
