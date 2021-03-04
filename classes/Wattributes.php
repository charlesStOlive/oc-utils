<?php namespace Waka\Utils\Classes;

use October\Rain\Support\Collection;

class Wattributes
{
    use \Waka\Utils\Classes\Traits\StringRelation;

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

    // public function getOne($dataSource, $key)
    // {
    //     $collection = $this->listAll();
    //     return $collection->where('key', $key)->first();
    // }

    public function listAll()
    {
        $all = new Collection();
        // $cloudiList = $this->listCloudis();
        // $montages = $this->listMontages();
        // $allImages = $allImages->merge($cloudiList);
        // $allImages = $allImages->merge($montages);
        //
        $listAttributes = $this->listAttributes();
        $listRelationsAttributes = $this->listRelation();
        //trace_log('listRelationsImages');
        //trace_log($listRelationsImages);
        $all = $all->merge($listAttributes);
        //$all = $all->merge($listAttributes);

        return $allImages;
    }

    public function listAttributes($model = null, $relation = null)
    {
        if (!$model) {
            $model = $this->model;
        }
        $modelClassName = get_class($model);
        $shortName = (new \ReflectionClass($modelClassName))->getShortName();
        $cloudiKeys = [];
        if (!$relation) {
            $relation = 'self';
        }

        $files = $model->attachOne;
        foreach ($files as $key => $value) {
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
        return $cloudiKeys;
    }

    public function listRelation()
    {
        $relationImages = new Collection();
        $relationWithImages = new Collection($this->relations);
        //trace_log($relationWithImages->toArray());
        if ($relationWithImages->count()) {
            $relationWithImages = $relationWithImages->where('images', true)->keys();
            foreach ($relationWithImages as $relation) {
                //trace_log($relation);
                $subModel = $this->getStringModelRelation($this->model, $relation);
                //trace_log($subModel->name);
                if (class_exists('\Waka\Cloudis\Classes\Cloudi')) {
                    $cloudiList = \Waka\Cloudis\Classes\Cloudi::listCloudis($subModel, $relation);
                    $montages = \Waka\Cloudis\Classes\Cloudi::listMontages($subModel, $relation);
                    $relationImages = $relationImages->merge($cloudiList);
                    $relationImages = $relationImages->merge($montages);
                }
                $files = $this->listFile($subModel, $relation);
            }
        }
        return $relationImages;
    }

    public function getPicturesUrl($dataImages)
    {
        if (!$dataImages) {
            return;
        }
        $allPictures = [];
        foreach ($dataImages as $image) {
            //trace_log($image);
            //On recherche le bon model
            $modelImage = $this->model;
            $img;

            if ($image['relation'] != 'self') {
                $modelImage = $this->getStringModelRelation($this->model, $image['relation']);
            }
            //trace_log("nom du model " . $modelImage->name);

            $options = [
                'width' => $image['width'] ?? null,
                'height' => $image['height'] ?? null,
                'crop' => $image['crop'] ?? null,
                'gravity' => $image['gravity'] ?? null,
            ];
            if ($image['type'] == 'cloudi') {
                $img = $modelImage->{$image['field']};
                if ($img) {
                    $img = $img->getUrl($options);
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
                if ($img) {
                    $img = $img->getThumb($options['width'], $options['height'], ['mode' => $options['crop']]);
                } else {
                    //trace_log('error');
                }
                // trace_log('montage ---' . $img);
            }
            $allPictures[$image['code']] = [
                'path' => $img,
                'width' => $options['width'],
                'height' => $options['height'],
            ];
        }
        return $allPictures;
    }
}
