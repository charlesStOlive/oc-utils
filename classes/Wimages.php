<?php namespace Waka\Utils\Classes;

use Carbon\Carbon;
use October\Rain\Support\Collection;

class Wimages
{
    use \Waka\Utils\Classes\Traits\StringRelation;

    private $model;
    private $class;
    public $testId;



    public function __construct($class, $testId, $relations, $model) {
        $this->class = $class;
        $this->testId = $testId;
        $this->model = $model;
        $this->relations = $relations;
    }

    public function instanciateModel($id = null)
    {
        if ($id) {
            $this->model = $this->class::find($id);
        } else if ($this->testId) {
            $this->model = $this->class::find($this->testId);
        } else {
            $this->model = $this->class::first();
        }
    }

    public function getAllPicturesKey($modelId = null)
    {
        $this->instanciateModel($modelId);
        $collection = $this->listAll($this->model);
        if ($collection) {
            return $collection->lists('name', 'key');
        } else {
            return null;
        }
    }

    public function getOnePictureKey($key, $modelId = null)
    {
        $this->instanciateModel($modelId);
        $collection = $this->listAll($this->model);
        return $collection->where('key', $key)->first();
    }


    // public function getOne($dataSource, $key)
    // {
    //     $collection = $this->listAll();
    //     return $collection->where('key', $key)->first();
    // }

    public function listAll($id=null) {
        $allImages = new Collection();
        if (class_exists('\Waka\Cloudis\Classes\Cloudi')) {
            $cloudiList = \Waka\Cloudis\Classes\Cloudi::listCloudis($this->model);
            $montages = \Waka\Cloudis\Classes\Cloudi::listMontages($this->model);
            $allImages = $allImages->merge($cloudiList);
            $allImages = $allImages->merge($montages);
        }
        //
        $listFiles = $this->listFile($this->model);
        $allImages = $allImages->merge($listFiles);

        $listRelationsImages = $this->listRelation();
        trace_log($listRelationsImages);
        $allImages = $allImages->merge($listRelationsImages);

        return $allImages;
    }

    public function listFile() {
        return [];
    }

    public function listRelation() {
        $relationImages = new Collection();
        $relationWithImages = new Collection($this->relations);
        trace_log($relationWithImages->toArray());
        if ($relationWithImages->count()) {
            $relationWithImages = $relationWithImages->where('images', true)->keys();
            foreach ($relationWithImages as $relation) {
                $subModel = $this->getStringModelRelation($this->model, $relation);
                trace_log($subModel->name);
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

    

    public function getPicturesUrl($dataImages)
    {
        if (!$dataImages) {
            return;
        }
        $allPictures = [];
        trace_log("--dataImages--");
        trace_log($dataImages);
        foreach ($dataImages as $image) {
            //trace_log($image);
            //On recherche le bon model
            $modelImage = $this->model;
            trace_log($this->model->name);
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

            // si cloudi ( voir GroupedImage )
            if ($image['type'] == 'cloudi') {
                $img = $modelImage->{$image['field']};
                if ($img) {
                    $img = $img->getUrl($options);
                } else {
                    $img = \Cloudder::secureShow(CloudisSettings::get('srcPath'));
                }
                // trace_log('image cloudi---' . $img);
            }
            // si montage ( voir GroupedImage )
            if ($image['type'] == 'montage') {
                $montage = $modelImage->montages->find($image['id']);
                $img = $modelImage->getCloudiModelUrl($montage, $options);
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