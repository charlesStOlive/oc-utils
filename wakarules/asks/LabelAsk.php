<?php namespace Waka\Utils\WakaRules\Asks;

use Waka\Utils\Classes\Rules\AskBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use Waka\Utils\Interfaces\Ask as AskInterface;

class LabelAsk extends AskBase implements AskInterface
{
    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function subFormDetails()
    {
        return [
            'name'        => 'Un label/titre',
            'description' => 'Ajoute un champs texte simple',
            'icon'        => 'wicon-pencil2',
            'share_mode'  => 'full',
            'premission'  => 'wcli.utils.ask.edit.admin',
            'subform_emit_field'    => 'txt',
            'show_attributes' => true,
            'outputs' => [
                'word_type' => null,
            ]
        ];
    }

    public function getText()
    {
        //trace_log('getText HTMLASK---');
        $hostObj = $this->host;
        //trace_log($hostObj->config_data);
        $text = $hostObj->config_data['txt'] ?? null;
        if($text) {
            return $text;
        }
        return parent::getText();

    }

    /**
     * $modelSrc le Model cible
     * $context le type de contenu twig ou word
     * $dataForTwig un modèle en array fournit par le datasource ( avec ces relations parents ) 
     */

    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {
        $text = $this->getConfig('txt');
        if(!$text) {
            throw new ApplicationException('le texte html du ask : '.$this->getCode().' n\'a pas été trouvé'); 
        }
        return \Twig::parse($text, $dataForTwig);
    }
}
