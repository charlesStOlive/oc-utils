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
    //use \Waka\Cloudis\Classes\Traits\CloudisKey;

    public $label;
    public $name;
    public $author;
    public $plugin;
    public $id;
    public $class;
    private $config;
    public $relations;
    public $otherRelations;
    public $emails;
    public $editFunctions;
    public $aggFunctions;
    public $modelId;
    public $model;
    public $testId;
    public $modelName;
    public $controller;
    public $aggs;
    public $wimages;

    public function __construct($id = null, $type_id = "name")
    {
        $globalConfig = new Collection($this->getSrConfig());
        $config = $globalConfig->where($type_id, $id)->first();
        //
        $this->config = $config;
        //
        $this->author = $config['author'] ?? null;
        $this->name = $config['name'] ?? null;
        $this->lowerName = strtolower($this->name);
        $this->plugin = $config['plugin'] ?? null;
        $this->class = $config['class'] ?? null;
        if (!$this->class) {
            throw new ApplicationException('Erreur data source model');
        }
        $this->id = $config['id'];
        //
        $label = $config['label'] ?? null;
        $this->label = $label ? $label : $this->name;
        //
        $controller = $config['controller'] ?? null;
        $this->controller = $controller ? $controller : strtolower($this->author) . '/' . strtolower($this->plugin) . '/' . str_plural($this->name);
        //
        $this->relations = $config['relations'] ?? null;
        $this->otherRelations = $config['otherRelations'] ?? null;
        //
        $this->emails = $config['emails'] ?? null;
        //
        $this->testId = $config['test_id'] ?? null;
        //
        $this->aggConfig = $config['aggs'] ?? null;
        //
        $this->editFunctions = $config['editFunctions'] ?? null;
        $this->aggFunctions = $config['aggFunctions'] ?? false;

        //
        $config = null;
        //
    }

    public function instanciateModel($id = null)
    {
        if ($id) {
            $this->model = $this->class::find($id);

        } else if ($this->testId) {
            $this->model = $this->class::find($this->testId);
        } else {
            throw new \SystemException('Il manque le test_id dans dataConfig');
        }
        $this->modelName = $this->model;
        $this->wimages = new Wimages($this->model, $this->relations);

    }
    public function getModel($modelId)
    {
        $this->instanciateModel($modelId);
        return $this->model;
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

    public function getPartialIndexOptions($productorModel, $relation = false)
    {

        $documents = $productorModel::where('data_source_id', $this->id)->get();

        if ($relation) {
            $documents = $documents->where('relation', '<>', null);
        } else {
            $documents = $documents->where('relation', '=', null);
        }

        $optionsList = [];

        foreach ($documents as $document) {
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
            $constructApi = $this->class::with($embedRelation)->find($this->model->id);
        } else {
            $constructApi = $this->class::find($this->model->id);
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
    public function getSimpleDotedValues($modelId = null)
    {
        $constructApi = $this->getValues($modelId);
        return array_dot($constructApi);
    }

    /**
     * FONCTIONS DE RECUPERATION DES IMAGES
     * les fonctions utulisent le trait CloudisKey
     */

    // private function getAllDataSourceImage()
    // {
    //     $allImages = new Collection();
    //     $listsImages = null;
    //     $listMontages = null;

    //     //si il y a le trait cloudi dans la classe il y a des images à chercher
    //     if (method_exists($this->model, 'getCloudisList')) {
    //         $listsImages = $this->model->getCloudisList();
    //         $listMontages = $this->model->getCloudiMontagesList();
    //     }

    //     if ($listsImages) {
    //         $allImages = $allImages->merge($listsImages);
    //     }
    //     if ($listMontages) {
    //         $allImages = $allImages->merge($listMontages);
    //     }
    //     $relationWithImages = new Collection($this->relations);
    //     if ($relationWithImages->count()) {
    //         $relationWithImages = $relationWithImages->where('image', true)->keys();
    //         foreach ($relationWithImages as $relation) {
    //             $subModel = $this->getStringModelRelation($this->model, $relation);
    //             $listsImages = $subModel->getCloudisList($relation);
    //             $listMontages = $subModel->getCloudiMontagesList($relation);
    //             if ($listsImages) {
    //                 $allImages = $allImages->merge($listsImages);
    //             }
    //             if ($listMontages) {
    //                 $allImages = $allImages->merge($listMontages);
    //             }
    //         }
    //     }

    //     return $allImages;
    // }

    /**
     * Utils for EMAIL ---------------------------------------------------
     * Fonctions d'identifications des contacts, utilises dans les popup de wakamail
     * getstringrelation est dans le trait StringRelation
     */
    public function getContact($type, $modelId = null)
    {
        $this->instanciateModel($modelId);
        $emailData = $this->emails[$type] ?? null;
        if (!$emailData) {
            throw new \ApplicationException("Les contacts ne sont pas correctement configurés.");
        }

        // trace_log("getContact emaildata | ");
        // trace_log($emailData);
        // trace_log($this->emails);
        // trace_log($type);

        if (!$emailData) {
            return;
        }
        $relation = $emailData['relation'] ?? null;
        $contacts;
        if ($relation) {
            $contacts = $this->getStringRelation($this->model, $relation);
        } else {
            $contacts = $this->model;
        }

        // trace_log($this->model->name);
        // trace_log($emailData['relations']);

        // trace_log("liste des relations pour contact");
        // trace_log($contacts->toArray());
        // trace_log($contacts['name']);
        // trace_log(get_class($contacts));

        $results = [];

        if (!$contacts) {
            return;
        }
        //On cherche si on a un l'email via la key
        $email = $contacts[$emailData['key']] ?? false;

        if ($email) {
            array_push($results, $email);
        } else {
            foreach ($contacts as $contact) {
                $email = $contact[$emailData['key']] ?? false;
                if ($email) {
                    array_push($results, $email);
                }
            }
        }
        //trace_log($results);
        return $results;

    }

    /**
     * UTILS FOR FUNCTIONS ------------------------------------------------------------
     */

    /**
     * retourne la liste des fonctions dans la classe de fonction liée à se data source.
     * Utiise par le formwifget functionlist et les wakamail, datasource, aggregator
     */
    public function getFunctionsList()
    {
        if (!$this->editFunctions) {
            throw new \ApplicationException("Il manque le chemin de la classe fonction dans DataSource pour ce model");
        }
        $fn = new $this->editFunctions;
        return $fn->getFunctionsList();
    }
    public function getFunctionsOutput($fnc)
    {
        if (!$this->editFunctions) {
            throw new \ApplicationException("Il manque le chemin de la classe fonction dans DataSource pour ce model");
        }
        $fn = new $this->editFunctions;
        return $fn->getFunctionsOutput($fnc);
    }
    /**
     * retourne simplement le function class. mis en fonction pour ajouter l'application exeption sans nuire à la lisibitilé de la fonction getFunctionsCollections
     */
    public function getFunctionClass()
    {
        if (!$this->editFunctions) {
            return null;
        }
        return new $this->editFunctions;
    }
    /**
     * Retourne les valeurs d'une fonction du model de se datasource.
     * templatemodel = wakamail ou document ou aggregator
     * id est l'id du model de datasource
     */

    public function getFunctionsCollections($modelId, $model_functions)
    {
        if (!$model_functions) {
            return;
        }
        $this->instanciateModel($modelId);

        $collection = [];
        $fnc = $this->getFunctionClass();
        $fnc->setModel($this->model);

        foreach ($model_functions as $item) {
            $itemFnc = $item['functionCode'];
            $collection[$item['collectionCode']] = $fnc->{$itemFnc}($item);
        }
        return $collection;
    }
    /**
     * Agg
     * retourne un object AggConfig;
     */
    public function getAggConfig()
    {
        if (class_exists('\Waka\Agg\Classes\AggConfig')) {
            return new \Waka\Agg\Classes\AggConfig($this->aggConfig, $this->class);
        } else {
            throw new \ApplicationException("Il manque le systhème Agg");
        }
    }

    /**
     * GLOBAL
     */

    public function getSrConfig()
    {
        $dataSource = Config::get('waka.wconfig::data_source.src');
        //trace_log($dataSource);
        if ($dataSource) {
            return Yaml::parseFile(plugins_path() . $dataSource);
        } else {
            return Yaml::parseFile(plugins_path() . '/waka/wconfig/config/datasources.yaml');
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
