<?php namespace Waka\Utils\Classes\Ds;

// use Lang;
// use MathPHP\Algebra;
// use MathPHP\Functions\Map;*
use ApplicationException;
use Config;
use Winter\Storm\Support\Collection;
use Winter\Storm\Extension\Extendable;
use Ds;
use Yaml;


class DataSource extends Extendable
{
    /**Clean */
    //
    public $config;
    public $code;
    public $class;
    //
    public $label;
    public $name;
    public $author;
    public $plugin;
    //
    public $relations;
    public $emails;
    public $controller;
    public $publications;
    public $attributes;
    public $outputName;
    //Après instanciation
    public $modelId;
    public $query;
    //
    public $modelPath;
    

    public function __construct($config, $key) {
        //
        
        $this->config = $config;
        $this->code = $key;
        $this->class = $config['class'] ?? null;
        if (!$this->class) {
            throw new ApplicationException('Erreur data source model class non défini');
        }
        //
        $explodedClass = explode('\\', $this->class);

        $this->name = $config['name'] ?? Ucfirst($config['label']);
        $firstExploded = array_shift($explodedClass);
        if($firstExploded) {
            $this->author = $firstExploded;
            //permet de gerer si la classe commence avec \
        } else {
            $this->author = array_shift($explodedClass);
        }
        $this->plugin = array_shift($explodedClass);

        $this->label = $config['label'] ?? null;
        if (!$this->label) {
            throw new ApplicationException('Il manque le label dans la config DataSource');
        }
        //
        $controller = $config['controller'] ?? null;
        $this->controller = $controller ? $controller : strtolower($this->author) . '/' . strtolower($this->plugin) . '/' . strtolower(camel_case($this->code)).'s';
        //
        $this->relations = $config['relations'] ?? [];
        //
        $attributes = $config['attributes'] ?? null;
        $this->attributes = $attributes ? $attributes : strtolower($this->author) . '/' . strtolower($this->plugin) . '/models/' . strtolower(camel_case($this->code)).'/attributes.yaml';
         //
        $modelPath = $config['modelPath'] ?? null;
        $this->modelPath = $modelPath ? $modelPath : strtolower($this->author) . '/' . strtolower($this->plugin) . '/models/' . strtolower(camel_case($this->code)).'/';
        //
        $this->emails = $config['emails'] ?? [];
        //
        $this->publications = $config['publications'] ?? [];
        /**Kill ? utilisé dans le datasource helper */
        $this->outputName = $config['outputName'] ?? 'name';
        parent::__construct();
        
    }

    private function instanciateQuery($id = null)
    {
        //trace_log('instanciateQuery');
        //trace_log($id);
        if ($this->query) {
            return;
        }
        if ($id) {
            $this->query = $this->class::find($id);
        } 
        if (!$this->query) {
            //trace_log('je cherche le premier');
            \Flash::error("ATTENTION : instanciateQuery impossible premier id trouvé instancié");
            //trace_log($this->class::first()->toArray());
            $this->query = $this->class::first();
        }
        if (!$this->query) {
            \Flash::error("ATTENTION : instanciateQuery impossible premier id trouvé instancié");
            throw new \SystemException("ID non trouvé ou Il n'y a pas de modele disponible pour : " . $this->class." Veuillez créer au moins une valuer dans cette ressource");
        }
    }


    public function getQuery($modelId = null)
    {
        $this->instanciateQuery($modelId);
        return $this->query;
    }

    public function getProductorOptions($productorModel, $modelId = null)
    {
       //trace_log('getProductorOptions');
       //trace_log($this->code);
        
        $productors;
        try {
            $productors = $productorModel::whereHas('waka_session', function($q) {
                $q->where('data_source', $this->code);
            })->active();
        } catch(\Exception $e) {
            \Log::error('pas de session pour le getProductorOptions > bascule sur le dataSource');
            $productors = $productorModel::where('data_source', $this->code);
        }
        

        $optionsList = [];

        foreach ($productors->get() as $productor) {
            $conditions = new \Waka\Utils\Classes\Conditions($productor, $this->getQuery($modelId));
            //trace_log($productor->name);

            if ($conditions->hasConditions()) {
                if ($conditions->checkConditions()) {
                    $optionsList[$productor->id] = $productor->name;
                } else {
                    \Event::fire('waka.utils::conditions.error', $conditions->getLogs());
                }
            } else {
                $optionsList[$productor->id] = $productor->name;
            }
        }
        return $optionsList;
    }
    public function getLotProductorOptions($productorModel)
    {
        $optionsList = [];
        $productors = $productorModel::whereHas('waka_session', function($q) {
            $q->where('data_source', $this->code);
        })->active()->get();
        foreach ($productors as $productor) {
            if ($productor->is_lot) {
                $optionsList[$productor->id] = $productor->name;
            }
        }
        return $optionsList;
    }
    public function getPartialIndexOptions($productorModel, $relation = false)
    {
        $productors = null;
        //trace_log("getPartialIndexOptions");
        //trace_log($this->code);
        try {
            $productors = $productorModel::whereHas('waka_session', function ($q) {
                $q->where('data_source', $this->code);
             })->get();
        } catch(\Exception $e) {
            \Log::error('pas de session pour le getPartialIndexOption > bascule sur le dataSource');
            $productors = $productorModel::where('data_source', $this->code);
        }
        //trace_log($productors->get()->toArray());
        

        if ($relation) {
            $productors = $productors->where('relation', '=', 1);
        } else {
            $productors = $productors->where('relation', '<>', 1);
        }
        //trace_log($productors->lists('name', 'id'));
        return $productors->lists('name', 'id');
    }

   
    /**
     * PARTIE PERMETTANT DE GERER LES SCOPES CAMPAGNES ??--------------
     */
    public function getScopesLists() {
        //trace_log("getScopesLists");
        $scopes = $this->config['scopes'] ?? [];
        $array = [];
        foreach($scopes as $key=>$scope) {
            $array[$key] = $scope['label'];

        }
        //trace_log($array);
        return $array;

    }
     public function getScopeOptions($key) {
        //trace_log("getScopeOptions");
         //trace_log($key);
        $scope = $this->config['scopes'][$key];
        //trace_log($scope);
        if($fromModel = $scope['options']['fromModel'] ?? false) {
            $nameFrom = $scope['nameFrom'] ?? 'name'; 
            return $fromModel::lists($nameFrom, 'id');
        } elseif ($fromSetting = $scope['options']['fromSetting'] ?? false) {
            return \Settings::get($fromSetting );
       } elseif ($fromSetting = $scope['options']['fromConfig'] ?? false) {
            return \Config::get($fromSetting );
       } elseif ($noOptions = $scope['noOptions'] ?? false) {
           return['no' => "Selection Inutile"];
       } else {
           throw new \ApplicationException('Probleme de configuration des scopes');
       }

    }

    /**
     * PARTIE PERMETTANT DE FUSIONNER LES DONNES -----------------
     */
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
    
    public function getFullQuery($modelId = null)
    {
        $this->instanciateQuery($modelId);
        $constructApi = $this->query;
        $attributeToAppend = $this->query->attributesToDs;
        if ($attributeToAppend) {
            foreach ($this->query->attributesToDs as $tempAppend) {
                $constructApi->append($tempAppend);
            }
        }
        $constructApi = $constructApi->toArray();
        $relation = $this->listRelation();
        $constructApi = array_merge($constructApi, $relation);
        return $constructApi;
    }

    public function getModelDataAndRelations($modelId = null)
    {
        $this->instanciateQuery($modelId);
        $constructApi = $this->query;
        $attributeToAppend = $this->query->attributesToDs;
        if ($attributeToAppend) {
            foreach ($this->query->attributesToDs as $tempAppend) {
                $constructApi->append($tempAppend);
            }
        }
        $relation = $this->listRelation();
        $constructApi->push($relation);
        return $constructApi;
    }

    public function listRelation()
    {
        //trace_log('**listRelation**');
        //trace_log($this->query->client);
        //trace_log();

        $results = [];
        $relations = new Collection($this->getKeyAndEmbed());
        if ($relations->count()) {
            foreach ($relations as $relation) {
                $subModel = array_get($this->query, $relation);
                if ($subModel) {
                    $relations = new Collection();
                    if ($subModel->attributesToDs) {
                        foreach ($subModel->attributesToDs as $tempAppend) {
                            //trace_log($subShortName . ' : ' . $tempAppend);
                            $subModel->append($tempAppend);
                        }
                    }
                    $results[$relation] = $subModel->toArray();
                }
                
            }
            return $results;
            
        } else {
            return [];
        }
    }

    public function getValues($modelId = null, $withInde = true)
    {
        $dsApi = array_merge($this->getFullQuery($modelId));
        return $dsApi;
    }

    /**
     * PARTIE SUR LES WORKFLOW ----------
     */
    public function getWorkflowState()
    {
        if (!$this->query) {
            throw new ApplicationException('model pas instancié pour la fonction getWorkflowState');
        }
        return $this->query->wfPlaceLabel();
    }

    /**
     * Utils for EMAIL ---------------------------------------------------
     * Fonctions d'identifications des contacts, utilises dans les popup de wakamail
     */
    public function getContact($type, $modelId = null)
    {
        $this->instanciateQuery($modelId);
        $emailData = $this->emails[$type] ?? null;

        if (!$emailData) {
            return [];
        }
        $relation = $emailData['relation'] ?? null;
        $contacts = null;
        if ($relation) {
            $contacts = array_get($this->query, $relation);
        } else {
            $contacts = $this->query;
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
     * RETROUVER LES BLOCS DE CONTENUS
     */
    


    /**
     * NOUVEAU PRINCIPE POUR LES IMAGES
     */


    public function getSrcImage() {
        $array = [];
        $array[$this->code] = $this->name;
        $relations = new Collection($this->relations);
        if ($relations->count()) {
            $relationArray =  $relations->where('images', true)->transform(function ($item, $key) {
                 $item = $item['label'];
                 return $item;
             })->toArray();
             $array = array_merge($array, $relationArray);
        }
        return $array; 
    }

    public function getImagesFilesFrom($type, $code = null, $attachMany= false) {
        $code ? $code : $this->code;
        $staticModel = new $this->class;
        //trace_log("code = ".$code);
        if($code != $this->code) {
            $relation = \DataSources::find($code);
            $staticModel = new $relation->class;
        }
        $files = [];
        //trace_log("AttachMany : ".$attachMany);
        if($attachMany) {
            $files = $staticModel->attachMany; 
        } else {
             $files = $staticModel->attachOne;
        }
        $fiesFiltered =  array_filter($files, function($value) use ($type) {
            return $value == [$type];
        });
        $finalArray = [];
        foreach($fiesFiltered as $key=>$file) {
            $finalArray[$key] = $key;
        }
        return  $finalArray;
    }

    /**
     * PARTIE SUR LES PUBLICATIONS ----
     */
    public function getPublicationsType()
    {
        $publications = $this->publications['types'] ?? false;
        if(!$publications) {
             throw new \ApplicationException("Il manque la configuration des publications dans le datasource");
        }
        return $this->publications['types'];
    }

    public function getPublicationsTypeLabel($key)
    {
        return $this->publications['types'][$key] ?? 'Inconnu';
    }

    public function getPublicationsFromType($class)
    {
        //Si il y a un point c est un attach many ou attachone
        $classIsNotProductor = strpos($class, '.');
        if (!$classIsNotProductor) {
            return $this->getPartialIndexOptions($class);
        } else {
            return null;
        }
    }

    public function getAttributesConfig() {
        $attributesConfig = null;
        if($this->attributes) {
            return \Yaml::parseFile(plugins_path('/'.$this->attributes));
        }
        return $attributesConfig['fields'] ?? [];
    }

    
}
