<?php namespace Waka\Utils\WakaRules\Asks;

use Waka\Utils\Classes\Rules\AskBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use Waka\Utils\Interfaces\Ask as AskInterface;
use System\Classes\ImageResizer;
use System\Classes\MediaLibrary;


class ImageAsk extends AskBase implements AskInterface
{
    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function subFormDetails()
    {
        return [
            'name'        => 'Une image dans le répertoire média',
            'description' => 'Choisissez Une image dans le répertoire média',
            'icon'        => 'icon-picture-o',
            'share_mode'  => 'full',
            'outputs' => [
                'word_type' => 'IMG',
            ]
        ];
    }

    public function listCropMode()
    {
        $config =  \Config::get('waka.utils::image.baseCrop');
        //trace_log($config);
        return $config;
        
    }

    public function getText()
    {
        $hostObj = $this->host;
        $url = $hostObj->config_data['image'] ?? null;
        $title = $hostObj->config_data['title'] ?? 'inc';
        if($url) {
            return "Titre : ".$title." | image : ".$url;
        }
        return parent::getText();

    }

    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {
        if(!$this->getConfig('image')) {
            throw new ApplicationException('Image non trouvé verifiez le champs image'); 
        }
        $path = MediaLibrary::url($this->getConfig('image'));
        $width = $this->getConfig('width');
        $height =  $this->getConfig('height');
        $crop = $this->getConfig('crop');
        if(!$crop) {
            $crop = "exact";
        }
        
        if($width && $height) {
            $path = ImageResizer::filterGetUrl($path,$width, $height, ['mode' => $crop]);
        }
        $imageobj =  [
                'path' => url($path),
                'width' => $width ? $width  . 'px' : null,
                'height' => $height ? $height  . 'px' : null,
                'title' => $this->getConfig('title'),
            ];
        return $imageobj;
    }
}
