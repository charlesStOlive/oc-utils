<?php namespace Waka\Utils\WakaRules\Contents;

use Waka\Utils\Classes\Rules\RuleContentBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use ToughDeveloper\ImageResizer\Classes\Image;
use Waka\Utils\Interfaces\RuleContent as RuleContentInterface;

class Html extends RuleContentBase implements RuleContentInterface
{
    protected $tableDefinitions = [];
    public $extendMediaTypes = [];

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

    public function listMediaType() {
        $baseType = [
            'nothing'=> 'Aucune',
            'linked' => 'Image pour ce post',
            'media'=> 'Image dans le répertoire média',
            'url'=>' Une Url {ds.disponible}',
        ];
        return array_merge($baseType, $this->extendMediaTypes);
    }

    public function listCropMode()
    {
        $config =  \Config::get('waka.utils::image.baseCrop');
        return $config;
    }

    /**
     * IS true
     */

    public function resolve($ds) {
        $staticImage = $this->getConfig('staticImage');
        $modePhoto;
        $objImage = null;

        $functionResolverName = 'dynamise'.studly_case($staticImage);
        if($this->methodExists($functionResolverName)) {
            $objImage = $this->$functionResolverName($ds);
        }

        //Création de la fonction dynamique en fonction de staticImage. Compliqué mais permet d'étendre les fonctions...
        $data = $this->getConfigs();
        $data['html'] = \Twig::parse($data['html'], $ds);
        //on ajoute toutes les données du formulaire
        $data = array_merge($data, ['image' => $objImage]);
        
        //trace_log($data);
        return $data;
    }

    public function dynamiseUrl($ds) {
        $width = $this->getConfig('opt_i_width');
        $height = $this->getConfig('opt_i_height');
        $url = $this->getConfig('url');
            //trace_log($path);
        $url= \Twig::parse($url,$ds);
            //$imageUrl = $image->resize($width, $height, [ 'mode' =>$crop ]);
        return [
                'path' => $url,
                'width' => $width ? $width  . 'px' : null,
                'height' => $height ? $height  . 'px' : null,
        ];
    }

    public function dynamiseMedia($ds) {
        $crop = $this->getConfig('opt_i_crop');
        $width = $this->getConfig('opt_i_width');
        $height = $this->getConfig('opt_i_height');
        $path = storage_path('app/media/' . $this->getConfig('image'));
            //trace_log($path);
        $image = new Image($path);
        $imageUrl = $image->resize($width, $height, [ 'mode' =>$crop ]);
        return [
                'path' => $imageUrl,
                'width' => $width ? $width  . 'px' : null,
                'height' => $height ? $height  . 'px' : null,
            ];
    }

    public function dynamiseLinked($ds) {
        $crop = $this->getConfig('opt_i_crop');
        $width = $this->getConfig('opt_i_width');
        $height = $this->getConfig('opt_i_height');
        return [
            'path' => $this->host->photo->getThumb($width, $height, ['mode' => $crop]),
            'width' => $width ? $width  . 'px' : null,
            'height' => $height ? $height  . 'px' : null,
        ];
    }

    
}
