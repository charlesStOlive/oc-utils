<?php namespace Waka\Utils\WakaRules\Contents;

use Waka\Utils\Classes\Rules\RuleContentBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use ToughDeveloper\ImageResizer\Classes\Image;
use Waka\Utils\Interfaces\RuleContent as RuleContentInterface;

class Html extends RuleContentBase implements RuleContentInterface
{
    protected $tableDefinitions = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function subFormDetails()
    {
        return [
            'name'        => 'Champs HTML + image',
            'description' => 'Un titre, un champs HTML et une image',
            'icon'        => 'icon-html5',
            'premission'  => 'wcli.utils.cond.edit.admin',
        ];
    }

    public function getText()
    {
        //trace_log('getText HTMLASK---');
        $hostObj = $this->host;
        //trace_log($hostObj->config_data);
        $text = $hostObj->config_data['title'] ?? null;
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
        
        
        $staticImage = $this->getConfig('staticImage');
        $modePhoto;
        $objImage = null;
        $crop = $this->getConfig('opt_i_crop');
        $width = $this->getConfig('opt_i_width');
        $height = $this->getConfig('opt_i_height');
        if($staticImage == 'linked') {
            $objImage = [
                'path' => $this->host->photo->getThumb($width, $height, ['mode' => $crop]),
                'width' => $width ? $width  . 'px' : null,
                'height' => $height ? $height  . 'px' : null,
            ];
        } elseif ($staticImage == 'media') {
            $path = storage_path('app/media/' . $this->getConfig('image'));
            //trace_log($path);
            $image = new Image($path);
            $imageUrl = $image->resize($width, $height, [ 'mode' =>$crop ]);
            $objImage = [
                    'path' => $imageUrl,
                    'width' => $width ? $width  . 'px' : null,
                    'height' => $height ? $height  . 'px' : null,
                ];
        }
        $data = $this->getConfigs();
        //on ajoute toutes les données du formulaire
        
        $data = array_merge($data, ['image' => $objImage]);
        //trace_log($data);
        return $data;
    }

    
}
