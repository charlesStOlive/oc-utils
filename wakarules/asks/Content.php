<?php namespace Waka\Utils\WakaRules\Asks;

use Waka\Utils\Classes\Rules\AskBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use Waka\Utils\Interfaces\Ask as AskInterface;

class Content extends AskBase  implements AskInterface
{
    use \Waka\Utils\Classes\Traits\StringRelation;


    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function subFormDetails()
    {
        return [
            'name'        => 'Contenu',
            'description' => 'Choisissez un bloc de contenu',
            'icon'        => 'icon-html5',
            'premission'  => 'wcli.utils.ask.edit.admin',
            'ask_emit'    => 'richeditor',
            'show_attributes' => true,
            'outputs' => [
                'word_type' => 'HTM',
            ]
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
        $modelSrcClassNameForLog = get_class($modelSrc);
        $modelSrcIdForLog = $modelSrc->id;

        $relation = $this->getConfig('relation');
        $contentCode = $this->getConfig('contentCode');
        $isRecursif = $this->getConfig('is_recursif');
        if($relation) {
            $modelSrc =  $this->getStringModelRelation($modelSrc, $relation);
        }
        if(!$modelSrc) {
            \Log::error("Erreur dans resolve content sur src ".$modelSrcClassNameForLog ." et ID : ".$modelSrcIdForLog);
            return null;
        }
        if($modelSrc->methodExists('getContent') && !$isRecursif) {
            return $modelSrc->getContent($contentCode);
        } else if($modelSrc->methodExists('getResursiveContent') && $isRecursif) {
            return $modelSrc->getResursiveContent($contentCode);
        } else {
            \Log::error('Le trait \Waka\Utils\Classes\Traits\WakaContent n existe pas');
            return null;
        }
    }
}
