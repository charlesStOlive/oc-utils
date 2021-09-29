<?php namespace Waka\Utils\AskRules;

use Waka\Utils\Classes\AskBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;

class ImageAsk extends AskBase
{
    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function askDetails()
    {
        return [
            'name'        => 'Une image dans le répertoire média',
            'description' => 'Choisissez Une image dans le répertoire média',
            'icon'        => 'icon-picture-o',
        ];
    }

    public function getText()
    {
        $hostObj = $this->host;
        $url = $hostObj->config_data['image'] ?? null;
        if($url) {
            return "image : ".$url;
        }
        return parent::getText();

    }
}
