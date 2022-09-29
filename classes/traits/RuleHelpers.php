<?php

namespace Waka\Utils\Classes\Traits;

use System\Classes\ImageResizer;
use System\Classes\MediaLibrary;

trait RuleHelpers
{


    public function listImageMode()
    {
        $baseType = [
            'nothing' => 'Aucune',
            'photo' => 'Image pour ce post',
            'media' => 'Image dans le répertoire média',
            'url' => ' Une Url {ds.disponible}',
        ];
        //return array_merge($baseType, $this->extendMediaTypes); TODO donner la possibilite d'etentdre cette fonction
        return $baseType;
    }

    public function listCropMode()
    {
        $config =  \Config::get('waka.utils::image.baseCrop');
        return $config;
    }


    protected function dynamiseUrl($ds, $options = [])
    {
        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;
        $url = $this->getConfig('url');
        try {
            $url = \Twig::parse($url, $ds);
        } catch (\Exception $ex) {
            /**/
            //trace_log('error parse url ' . $this->host->code);
        }

        //$imageUrl = $image->resize($width, $height, [ 'mode' =>$crop ]);
        return [
            'path' => $url,
            'width' => $width ? $width  . 'px' : null,
            'height' => $height ? $height  . 'px' : null,
        ];
    }

    protected function dynamiseMedia($ds, $options = [])
    {
        $crop = $options['crop'] ?? 'exact';
        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;
        $path = MediaLibrary::url($this->getConfig('media'));


        if ($width && $height) {
            $path = ImageResizer::filterGetUrl($path, $width, $height, ['mode' => $crop]);
        }
        //$path = $image
        return [
            'path' => $path,
            'width' => $width ? $width  . 'px' : null,
            'height' => $height ? $height  . 'px' : null,
        ];
    }




    protected function dynamisePhoto($ds, $options = [])
    {
        //trace_log("dynamisePhoto : ".$this->getCode());
        $crop = $options['crop'] ?? 'exact';
        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;
        $objetImage = [
            'path' => $this->host->photo->getThumb($width, $height, ['mode' => $crop]),
            'width' => $width ? $width  . 'px' : null,
            'height' => $height ? $height  . 'px' : null,
        ];
        //trace_log($objetImage);
        return $objetImage;
    }


    public function getMignatureAttribute()
    {
        $imageMode = $this->getConfig('imageMode');
        //trace_log($imageMode);
        $objImage = null;
        $functionResolverName = 'dynamise' . studly_case($imageMode);
        $options = [
            'width' => 40,
            'height' => 40,
            'crop' => 'exact',
        ];
        if ($this->methodExists($functionResolverName)) {
            $objImage = $this->$functionResolverName([], $options);
        } else {
            //\Log::error($functionResolverName." n'existe pas");
        }
        return $objImage['path'] ?? null;
    }

    protected function listsNested($array, $value, $key = null, $indent = '-')
    {
        /*
         * Recursive helper function
         */
        $buildCollection = function ($items, $depth = 0) use (&$buildCollection, $value, $key, $indent) {
            $result = [];

            $indentString = str_repeat($indent, $depth);
            foreach ($items as $item) {

                if ($key !== null) {
                    $result[$item[$key]] = $indentString . $item[$value];
                } else {
                    $result[] = $indentString . $item[$value];
                }

                /*
                 * Add the children
                 */
                $childItems = $item['children'] ?? [];
                if (count($childItems) > 0) {
                    $result = $result + $buildCollection($childItems, $depth + 1);
                }
            }

            return $result;
        };

        /*
         * Build a nested collection
         */
        $rootItems = $array;
        return $buildCollection($rootItems);
    }

    public function filterFields($fields, $context = null)
    {
        //trace_log(isset($fields->imageMode));
        if (!isset($fields->imageMode)) {
            return;
        }
        $fields->photo->hidden = true;
        $fields->media->hidden = true;
        $fields->url->hidden = true;
        if ($fields->imageMode->value == 'photo') {
            $fields->media->value = '';
            $fields->photo->hidden = false;
        }
        if ($fields->imageMode->value == 'url') {
            $fields->media->value = '';
            $fields->url->hidden = false;
        }
        if ($fields->imageMode->value == 'media') {
            $fields->media->hidden = false;
        }
    }
}
