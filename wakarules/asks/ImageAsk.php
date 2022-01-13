<?php namespace Waka\Utils\WakaRules\Asks;

use Waka\Utils\Classes\Rules\AskBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use ToughDeveloper\ImageResizer\Classes\Image;
use Waka\Utils\Interfaces\Ask as AskInterface;


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
        $path = storage_path('app/media/' . $this->getConfig('image'));
        //trace_log($path);
        $image = new Image($path);
        $imageUrl = $image->resize($this->getConfig('width'), $this->getConfig('height'), [ 'mode' =>$this->getConfig('crop') ]);
        $imageobj = [
                        'path' => $imageUrl->getCachedImagePath(false),
                        'width' => $this->getConfig('width') . 'px',
                        'height' => $this->getConfig('height') . 'px',
                        'title' => $this->getConfig('title'),
                    ];
        //trace_log($imageobj);
        return $imageobj;
    }
}
