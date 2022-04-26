<?php namespace Waka\Utils\WakaRules\Asks;

use Waka\Utils\Classes\Rules\AskBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use Waka\Utils\Interfaces\Ask as AskInterface;

class FilesImgsLinkeds extends AskBase implements AskInterface
{
    public $jsonable = [
        'imagesNames',
    ];

    /**
     * Returns information about this event, including name and description.
     */
    public function subFormDetails()
    {
        return [
            'name'        => 'Des image liée',
            'description' => 'Des images liées dans une liaison. Filtrable par nom avec limite possible.',
            'share_mode' => 'ressource',
            'icon'        => 'icon-picture-o',
            'outputs' => [
                'word_type' => 'IMG',
            ]
        ];
    }

    public function defineValidationRules()
    {
        return [
            'srcImage' => 'required',
            'image' => 'required',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
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
        $src = $this->getDs();
        if(!$src) {
            return [];
        }
        $code = $this->host->srcImage ?? $this->getDs()->code;
        $src = $this->getDs()->getImagesFilesFrom('System\Models\File', $code, true);
        return $src;
    }
    public function listCropMode()
    {
        $config =  \Config::get('waka.utils::image.baseCrop');
        //trace_log($config);
        return $config;
        
    }

    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {
        $clientModel = $modelSrc;
        //$clientModel = $this->getClientModel($clientId);
        $finalModel = null;
        //get configuration
        $configs = $this->host->config_data;
        $keyImage = $configs['image'] ?? null;
        $src = $configs['srcImage'] ?? null;
        $imagesNames = $this->getConfig('imagesNames');
        $width = $configs['width'] ?? null;
        $height = $configs['height'] ?? null;
        $quality = $configs['quality'] ?? 1;
        
        $imgWidth = $width *   floatval($quality);
        $imgHeight =  $height *   floatval($quality);

        $crop = $configs['crop'] ?? 'exact';
        
        //creation de la donnés
        trace_log($src);
        trace_log($this->getDs()->code);
        if($src != $this->getDs()->code) {
            $finalModel = $clientModel->{$src};
        } else {
            $finalModel = $clientModel;
        }
        trace_log($finalModel->name);
        $finalResult = [];
        $finalResult['title'] = $this->getConfig('title');
        $finalResult['images'] = [];
        trace_log($finalModel->{$keyImage}->toArray());
        foreach($imagesNames as $name) {
            
            $finalImage = $finalModel->{$keyImage}()->where('title', $name)->first();
            if($finalImage) {
                $objImage = [
                    'path' => url($finalImage->getThumb($imgWidth, $imgHeight, ['mode' => $crop])),
                    'width' => $width . 'px',
                    'height' => $height . 'px',
                    //Pour word
                    'ratio' => true,
                ];
                $finalResult['images'][$name] = $objImage;
                
            }
        }
        trace_log($finalResult);
        return $finalResult;
    }
}
