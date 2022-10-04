<?php namespace Waka\Utils\Classes;

use Winter\Storm\Support\Collection;

class Wimages
{
    private $model;
    protected $relations;

    public function __construct($model, $relations = [])
    {
        $this->model = $model;
        $this->relations = $relations;
    }

    public function getAllPicturesKey()
    {
        $collection = $this->listAll($this->model);
        if ($collection) {
            return $collection->lists('name', 'key');
        } else {
            return null;
        }
    }

    public function getOnePictureKey($key)
    {
        $collection = $this->listAll($this->model);
        return $collection->where('key', $key)->first();
    }

    public function listAll()
    {
        $allImages = new Collection();
        $cloudiList = $this->listCloudis();
        $montages = $this->listMontages();
        $allImages = $allImages->merge($cloudiList);
        $allImages = $allImages->merge($montages);
        //
        $listFiles = $this->listFile();
        $allImages = $allImages->merge($listFiles);

        $listRelationsImages = $this->listRelation();
        // trace_log('listRelationsImages');
        // trace_log($listRelationsImages);
        $allImages = $allImages->merge($listRelationsImages);

        return $allImages;
    }
    public function listCloudis()
    {
        if (class_exists('\Waka\Cloudis\Classes\Cloudi')) {
            return \Waka\Cloudis\Classes\Cloudi::listCloudis($this->model);
        } else {
            return [];
        }
    }
    public function listMontages()
    {
        if (class_exists('\Waka\Cloudis\Classes\Cloudi')) {
            return \Waka\Cloudis\Classes\Cloudi::listMontages($this->model);
        } else {
            return [];
        }
    }

    public function listFile($model = null, $relation = null)
    {
        //trace_log("listFile");
        if (!$model) {
            $model = $this->model;
        }
        if(!$model) {
            return [];
        }
        $modelClassName = get_class($model);
        $shortName = (new \ReflectionClass($modelClassName))->getShortName();
        $cloudiKeys = [];
        if (!$relation) {
            $relation = 'self';
        }

        $files = $model->attachOne;
        //trace_log($model->attachOne);
        foreach ($files as $key => $value) {
            if(is_array($value)) {
                 $value =  $value[0];
            }
            if ($value == 'System\Models\File') {
                $img = [
                    'field' => $key,
                    'type' => 'file',
                    'relation' => $relation,
                    'key' => $shortName . '_' . $key,
                    'name' => $shortName . ' : ' . $key . ' (Image)',
                ];
                array_push($cloudiKeys, $img);
            }
        }
        //trace_log($cloudiKeys);
        return $cloudiKeys;
    }

    public function listRelation()
    {
        //trace_log('listRelation');
        $relationImages = new Collection();
        $relationWithImages = new Collection($this->relations);
        //trace_log($relationWithImages->toArray());
        if ($relationWithImages->count()) {
            $relationWithImages = $relationWithImages->where('images', true)->keys();
            foreach ($relationWithImages as $relation) {
                //trace_log($relation);
                $subModel = array_get($this->model, $relation);
                $files = $this->listFile($subModel, $relation);
                //trace_log($subModel->name);
                $relationImages = $relationImages->merge($files);
                if (class_exists('\Waka\Cloudis\Classes\Cloudi')) {
                    $cloudiList = \Waka\Cloudis\Classes\Cloudi::listCloudis($subModel, $relation);
                    $montages = \Waka\Cloudis\Classes\Cloudi::listMontages($subModel, $relation);
                    $relationImages = $relationImages->merge($cloudiList);
                    $relationImages = $relationImages->merge($montages);
                }  
            }
        }
        return $relationImages;
    }
    public function getUrlFromOptions($objImage) {
        //trace_log($objImage);
        $pictureData = $this->getOnePictureKey($objImage['selector'] ?? []);
        //trace_log($pictureData);
        $objImage = array_merge($objImage, $pictureData);
        return $this->getOnePictureUrl($objImage);


        
    }
    public function getOnePictureUrl($image) {
            $modelImage = $this->model;
            $img = '';

            if ($image['relation'] != 'self') {
                $modelImage = array_get($this->model, $image['relation']);
            }
            //trace_log("nom du model " . $modelImage->name);

            $width = $image['width'] ?? null;
            $height = $image['height'] ?? null;
            $crop = $image['crop'] ?? null;
            $gravity = $image['gravity'] ?? null;

            $options = [
                'width' => $image['width'] ?? null,
                'height' => $image['height'] ?? null,
                'crop' => $image['crop'] ?? null,
                'gravity' => $image['gravity'] ?? null,
            ];

            //trace_log($options);

            if ($image['type'] == 'cloudi') {
                $img = $modelImage->{$image['field']};
                if ($img) {
                    $img = $img->getUrl($options);
                    //$img = $img->getCloudiUrl($width,  $height, $crop, $gravity);
                } else {
                    $img = \Cloudder::secureShow(CloudisSettings::get('srcPath'));
                }
                // trace_log('image cloudi---' . $img);
            }
            if ($image['type'] == 'montage') {
                $montage = $modelImage->montages->find($image['id']);
                $img = $modelImage->getMontage($montage, $options);
                // trace_log('montage ---' . $img);
            }
            if ($image['type'] == 'file') {
                $img = $modelImage->{$image['field']};
                //trace_log("image à trouver ".$image['field']);
                if ($img) {
                    $img = $img->getThumb($options['width'], $options['height']);
                } else {
                    //trace_log('error');
                }
                //trace_log('montage ---' . $img);
            }
            return  [
                'path' => $img,
                'width' => $options['width'],
                'height' => $options['height'],
            ];

    }

    public function getPicturesUrl($dataImages)
    {
        //trace_log('getPicturesUrl');
        if (!$dataImages) {
            return;
        }
        $allPictures = [];
        foreach ($dataImages as $image) {
            //trace_log($image);
            //On recherche le bon model
             $allPictures[$image['code']] = $this->getOnePictureUrl($image);
            
        }
        return $allPictures;
    }
}
