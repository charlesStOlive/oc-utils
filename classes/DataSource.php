<?php namespace Waka\Utils\Classes;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*
use ApplicationException;
use Config;
use October\Rain\Support\Collection;
use Yaml;

class DataSource
{
    use \Waka\Utils\Classes\Traits\StringRelation;
    use \Waka\Cloudis\Classes\Traits\CloudisKey;

    public $name;
    public $id;
    public $modelClass;
    private $config;
    public $relations;
    public $otherRelations;
    public $emails;
    public $editFunctions;
    public $aggFunctions;
    public $modelId;
    public $model;

    public function __construct($id = null, $type_id = "name")
    {
        $globalConfig = new Collection($this->getSrConfig());
        $config = $globalConfig->where($type_id, $id)->first();
        $this->modelClass = $config['model'] ?? false;
        if (!$this->modelClass) {
            throw new ApplicationException('Erreur data source model');
        }
        $this->config = $config;
        $this->id = $config['id'];
        //
        $this->relations = $config['relations'] ?? null;
        $this->otherRelations = $config['otherRelations'] ?? false;
        //
        $this->emails = $config['emails'] ?? false;
        //
        $this->editFunctions = $config['editFunctions'] ?? null;
        $this->aggFunctions = $config['aggFunctions'] ?? false;

        $config = null;
    }

    public function instanciateModel($id = null)
    {
        if ($id) {
            $this->model = $this->modelClass::find($id);
        } else {
            $this->model = $this->modelClass::first();
        }
    }

    /**
     * TRAVAIL SUR LES PRODUCTOR
     */

    public function getPartialOptions($modelId = null, $productorModel)
    {

        $documents = $productorModel::where('data_source_id', $this->id);
        $this->instanciateModel($modelId);

        $optionsList = [];

        foreach ($documents->get() as $document) {
            if ($document->is_scope) {
                //Si il y a des limites
                $scope = new \Waka\Utils\Classes\Scopes($document, $this->model);
                if ($scope->checkScopes()) {
                    $optionsList[$document->id] = $document->name;
                }
            } else {
                $optionsList[$document->id] = $document->name;
            }
        }
        return $optionsList;
    }

    public function getPartialIndexOptions($productorModel)
    {

        $documents = $productorModel::where('data_source_id', $this->id);

        $optionsList = [];

        foreach ($documents->get() as $document) {
            if ($document->is_scope) {
                //Si il y a des limites
                $scope = new \Waka\Utils\Classes\Scopes($document);
                if ($scope->checkIndexScopes()) {
                    $optionsList[$document->id] = $document->name;
                }
            } else {
                $optionsList[$document->id] = $document->name;
            }
        }
        return $optionsList;
    }

    public function getKeyAndEmbed()
    {
        if (!$this->relations) {
            return null;
        }
        $array = array_keys($this->relations);

        foreach ($this->relations as $key => $relation) {
            if ($relation['embed'] ?? false) {
                foreach ($relation['embed'] as $subRelation) {
                    array_push($array, $key . '.' . $subRelation);
                }
            }
        }
        return $array;

    }

    /**
     * RECUPERATION DES VALEURS DES MODELES ET DE LEURS LIAISON
     */

    public function getModels($modelId = null)
    {
        $this->instanciateModel($modelId);

        $constructApi = null;
        $embedRelation = $this->getKeyAndEmbed();
        if ($embedRelation) {
            $constructApi = $this->modelClass::with($embedRelation)->find($this->model->id);
        } else {
            $constructApi = $this->modelClass::find($this->model->id);
        }
        return $constructApi;
    }

    public function getOtherRelationValues()
    {
        if (!$this->otherRelations) {
            return [];
        }
        $otherRelation = [];
        foreach ($this->otherRelations as $key => $otherRelation) {
            $class = new $otherRelation['class'];
            $class = $class::first()->toArray();
            $otherRelation[$key] = $class;
        }
        return $otherRelation;
    }

    public function getValues($modelId = null, $withInde = true)
    {
        $dsApi = array_merge($this->getModels($modelId)->toArray(), $this->getOtherRelationValues());
        return $dsApi;
    }

    public function getDotedValues($modelId = null)
    {
        $constructApi = $this->getValues($modelId);
        $api[snake_case($this->name)] = $constructApi;
        return array_dot($api);
    }

    /**
     * Cette fonction utulise le trait CloudisKey
     */
    public function getAllPicturesKey($id = null)
    {
        //Recherche du model
        $this->instanciateModel($modelId);

        $gi = new \Waka\Cloudis\Classes\GroupedImages($this->model);
        return $gi->getLists($this);

    }

    public function getOnePictureKey($key, $id = null)
    {
        //Recherche du model
        $this->instanciateModel($modelId);

        $gi = new \Waka\Cloudis\Classes\GroupedImages($this->model);
        return $gi->getOne($this, $key);

    }

    public function getPicturesUrl($id, $dataImages)
    {
        if (!$dataImages) {
            return;
        }

        $this->instanciateModel($modelId);
        $allPictures = [];
        // trace_log("--dataImages--");
        // trace_log($dataImages);
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

    /**
     * GLOBAL
     */

    public function getSrConfig()
    {
        $dataSource = Config::get('waka.crsm::data_source.src');
        trace_log($dataSource);
        if ($dataSource) {
            return Yaml::parseFile(plugins_path() . $dataSource);
        } else {
            trace_log("datasource pas trouve");
            return Yaml::parseFile(plugins_path() . '/waka/crsm/config/datasources.yaml');
        }

    }

    public function getControllerUrlAttribute()
    {
        return strtolower($this->getConfigValue('author') . '\\'
            . $this->getConfigValue('plugin') . '\\'
            . $this->getConfigValue('controller'));
    }

    public function getConfigValue($key)
    {
        return $this->config[$key];
    }

}
