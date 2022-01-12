<?php namespace Waka\Utils\WakaRules\Contents;

use Waka\Utils\Classes\Rules\RuleContentBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use ToughDeveloper\ImageResizer\Classes\Image;

class Md extends RuleContentBase
{
    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function ruleDetails()
    {
        return [
            'name'        => 'Champs Marque down',
            'description' => 'Un simple champs Markdown.',
            'icon'        => 'icon-md5',
            'premission'  => 'wcli.utils.cond.edit.admin',
        ];
    }

    public function getText()
    {
        //trace_log('getText HTMLASK---');
        $hostObj = $this->host;
        //trace_log($hostObj->config_data);
        $text = $hostObj->config_data['md'] ?? null;
        if($text) {
            return $text;
        }
        return parent::getText();

    }

    public function listCropMode()
    {
        $config =  \Config::get('waka.utils::image.baseCrop');
        //trace_log($config);
        return $config;
        
    }

    /**
     * IS true
     */

    public function resolve() {
        $data = $this->getConfigs();
        return $data;
    } 
}