<?php namespace Waka\Utils\AskRules;

use Waka\Utils\Classes\AskBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;

class FileImgLinked extends AskBase
{
    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function askDetails()
    {
        return [
            'name'        => 'Une image liée',
            'description' => 'Une image du modèle ou d\'un modèle parent',
            'icon'        => 'icon-picture-o',
        ];
    }

    public function defineValidationRules()
    {
        return [
            'srcImage' => 'required',
            'image' => 'required',
        ];
    }

    public function getText()
    {
        $hostObj = $this->host;
        $url = $hostObj->config_data['image'] ?? null;
        $src = $hostObj->config_data['srcImage'] ?? null;
        if($url) {
            return "image : ".$url. " | "."source : ".$src;
        }
        return parent::getText();

    }

    public function listSelfParent()
    {
        $src = $this->getDs();
        if($src) {
            return $src->getSrcImage();
        }
        return [];
    }

    public function listLinkedImage()
    {
        trace_log($this);
        $src = $this->getDs();
        if(!$src) {
            return [];
        }
        $code = $this->host->srcImage ?? $this->getDs()->code;
        trace_log($code);
        $src = $this->getDs()->getImagesFilesFrom('System\Models\File', $code);
        trace_log($src);
        return $src;
        
    }
    public function istCropMode()
    {
        $config =  \Config::get('waka.utils::image.baseCrop');
        trace_log($config);
        return $config;
        
    }
}
