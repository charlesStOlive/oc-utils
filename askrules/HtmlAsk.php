<?php namespace Waka\Utils\AskRules;

use Waka\Utils\Classes\AskBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;

class HtmlAsk extends AskBase
{
    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function askDetails()
    {
        return [
            'name'        => 'texte HTML',
            'description' => 'Ajoute un champs HTML',
            'icon'        => 'icon-html5',
            'premission'  => 'wcli.utils.ask.edit.admin',
        ];
    }

    public function getText()
    {
        //trace_log('getText HTMLASK---');
        $hostObj = $this->host;
        //trace_log($hostObj->config_data);
        $text = $hostObj->config_data['html'] ?? null;
        if($text) {
            return $text;
        }
        return parent::getText();

    }
}
