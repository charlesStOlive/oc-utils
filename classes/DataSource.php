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
    public $code;
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
    public $attributesConfig;
    public $outputName;
    public $publications;

    public function __construct($id = null, $type_id = "code")
    {
        $globalConfig = new Collection($this->getSrConfig());
        $config = $globalConfig->where($type_id, $id)->first();
        //
        $this->config = $config;
        //
        $this->author = $config['author'] ?? null;
        $this->name = $config['name'] ?? null;
        $this->plugin = $config['plugin'] ?? null;
        $this->class = $config['class'] ?? null;

        $this->lowerName = strtolower($this->name);

        $this->code = $config['code'] ?? $this->lowerName;
        if (!$this->class) {
            throw new ApplicationException('Erreur data source model');
        }
        //$this->id = $config['id'];
        $this->code = $config['code'];
        //
        $label = $config['label'] ?? null;
        $this->label = $label ? $label : $this->name;
        //
        $controller = $config['controller'] ?? null;
        $this->controller = $controller ? $controller : strtolower($this->author) . '/' . strtolower($this->plugin) . '/' . str_plural($this->name);
        //
        $this->attributesConfig = $config['attributes'] ?? null;
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

        $this->outputName = $config['outputName'] ?? 'name';

        $this->publications = $config['publications'] ?? [];

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
        if (!$this->model) {
            // \Flash::error("Attention le test_id n'existe pas");
            $this->model = $this->class::first();
        }
        if (!$this->model) {
            // \Flash::error("Attention le test_id n'existe pas");
            throw new \SystemException("Il n'y a pas de modele disponible pour : " . $this->class);
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

        $documents = $productorModel::where('data_source', $this->code);
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
        $documents = $productorModel::where('data_source', $this->code)->get();

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
        $constructApi = $this->model;
        $attributeToAppend = $this->model->attributesToDs;
        if ($attributeToAppend) {
            foreach ($this->model->attributesToDs as $tempAppend) {
                $constructApi->append($tempAppend);
            }
        }
        $constructApi = $constructApi->toArray();
        $relation = $this->listRelation();
        $constructApi = array_merge($constructApi, $relation);
        return $constructApi;
    }

    public function listRelation()
    {
        $results = [];
        $relations = new Collection($this->getKeyAndEmbed());
        if ($relations->count()) {
            foreach ($relations as $relation) {
                //trace_log($relation);
                $subModel = $this->getStringModelRelation($this->model, $relation);
                if ($subModel) {
                    $subModelClassName = get_class($subModel);
                    $subShortName = (new \ReflectionClass($subModelClassName))->getShortName();
                    $relations = new Collection();
                    if ($subModel->attributesToDs) {
                        foreach ($subModel->attributesToDs as $tempAppend) {
                            //trace_log($subShortName . ' : ' . $tempAppend);
                            $subModel->append($tempAppend);
                        }
                    }
                    $subRelation = explode('.', $relation);
                    if (count($subRelation) == 1) {
                        $results[$relation] = $subModel->toArray();
                    }
                    if (count($subRelation) == 2) {
                        $results[$subRelation[0]][$subRelation[1]] = $subModel->toArray();
                    }
                    if (count($subRelation) == 3) {
                        $results[$subRelation[0]][$subRelation[1]][$subRelation[2]] = $subModel->toArray();
                    }

                }

            }
            return $results;
        } else {
            return [];
        }
    }

    public function getValues($modelId = null, $withInde = true)
    {
        $dsApi = array_merge($this->getModels($modelId));
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
     * Prend la valeur du workflow
     */
    public function getWorkflowState()
    {
        if (!$this->model) {
            throw new ApplicationException('model pas instancié pour la fonction getWorkflowState');
        }
        return $this->model->wfPlaceLabel();
    }

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
            return [];
        }
        $relation = $emailData['relation'] ?? null;
        $contacts;
        if ($relation) {
            $contacts = $this->getStringRelation($this->model, $relation);
        } else {
            $contacts = $this->model;
        }

        $results = [];

        if (!$contacts) {
            return [];
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
