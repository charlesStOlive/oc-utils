<?php namespace Waka\Utils\WakaRules\Asks;

use Waka\Utils\Classes\Rules\AskBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use Waka\Utils\Interfaces\Ask as AskInterface;


class HtmlAsk extends AskBase implements AskInterface
{
    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function subFormDetails()
    {
        return [
            'name'        => 'texte HTML',
            'description' => 'Ajoute un champs HTML',
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
        $text = $hostObj->config_data['html'] ?? null;
        if($text) {
            return strip_tags($text, '<p><br><b><strong><i><em>');
        }
        return parent::getText();

    }

    /**
     * $modelSrc le Model cible
     * $context le type de contenu twig ou word
     * $dataForTwig un modèle en array fournit par le datasource ( avec ces relations parents ) 
     */

    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {
        $text = $this->host->config_data['html'] ?? null;
        if(!$text) {
            throw new ApplicationException('le texte html du ask : '.$this->getCode().' n\'a pas été trouvé'); 
        }
        if($context == 'txt') {
            return strip_tags(\Twig::parse($text, $dataForTwig));
        } else {
            return \Twig::parse($text, $dataForTwig);;
        }
        
    }
}
