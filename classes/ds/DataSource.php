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
    use \Waka\Utils\Classes\Traits\StringRelation;
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
    public $model;
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

        $this->name = Ucfirst($config['label']);
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
        $this->controller = $controller ? $controller : strtolower($this->author) . '/' . strtolower($this->plugin) . '/' . strtolower($this->name).'s';
        //
        $this->relations = $config['relations'] ?? [];
        //
        $attributes = $config['attributes'] ?? null;
        $this->attributes = $attributes ? $attributes : strtolower($this->author) . '/' . strtolower($this->plugin) . '//models/' . strtolower($this->name);
         //
        $modelPath = $config['modelPath'] ?? null;
        $this->modelPath = $modelPath ? $modelPath : strtolower($this->author) . '/' . strtolower($this->plugin) . '//models/' . strtolower($this->name).'/';
        //
        $this->emails = $config['emails'] ?? [];
        //
        $this->publications = $config['publications'] ?? [];
        /**Kill ? utilisé dans le datasource helper */
        $this->outputName = $config['outputName'] ?? 'name';
        parent::__construct();
        
    }

    public function instanciateModel($id = null)
    {
        
        if ($this->model) {
            return;
        }
        if ($id) {
            $this->model = $this->class::find($id);
        } 
        if (!$this->model) {
            /**/trace_log('ATTENTION : instanciateModel impossible');
            $this->model = $this->class::first();
            \Flash::error("ATTENTION : instanciateModel impossible premier id trouvé instancié");
            //throw new \SystemException("ID non trouvé ou Il n'y a pas de modele disponible pour : " . $this->class." Veuillez créer au moins une valuer dans cette ressource");
        }
    }

    public function getModel($modelId)
    {
        $this->instanciateModel($modelId);
        return $this->model;
    }

    public function getProductorOptions($productorModel, $modelId = null)
    {

        $documents = $productorModel::where('data_source', $this->code);
        $this->instanciateModel($modelId);/**NETOYAGE**/

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
    public function getLotProductorOptions($productorModel)
    {
        $documents = $productorModel::where('data_source', $this->code)->get();
        $optionsList = [];
        foreach ($documents as $document) {
            if ($document->is_lot) {
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

    /**NETOYAGE**/
    public function dynamyseText($content,$modelId =null) {
        if($modelId) {
            $this->instanciateModel($modelId);
        }
        if(!$this->model) {
            throw new \SystemException('dynamyseText impossible le modèle est pas instancié ! ');
        }
        return \Twig::parse($content, ['ds' => $this->model]);
    }

    /**NETOYAGE**/
    public function getProductorAsks($productorClass, $productorId, $modelId)
    {
        if(!$productorId) {
             throw new \SystemException('le productorId est null ! ');
        }
        $productor = $productorClass::find($productorId);
        if(!$productor->rule_asks()->count()) {
            return [];
        }
        $this->instanciateModel($modelId);
        $asksList = [];
        $asks = $productor->rule_asks()->get();
        foreach ($asks as $ask) {
            if($ask->isEditable()) {
                $askCode = $ask->getCode();
                $askContent = $ask->resolve($this->model, 'twig', ['ds' =>$this->getValues()]);
                $askType = $ask->getEditableOption();
                $asksList['_ask_'.$askCode] = [
                    'label' => "Pré remplissage de  : ".$askCode,
                    'default' => $askContent,
                    'type' => $askType,
                    'size'=> $askType  == 'textarea' ? 'tiny' : 'small',
                    'toolbarButtons' => $askType  == 'richeditor' ? 'bold|italic' : null,
                ];

            }
        }
        return $asksList;
    }

    
    public function getAsksFromData($datas = [], $modelAsks = []) {
        $askArray = [];
        if($datas) {
            foreach($datas as $key=>$data) {
                if(starts_with($key, '_ask_')) {
                    $finalKey = str_replace('_ask_', '', $key);
                    $askArray[$finalKey] = $data;
                }
            }
        } 
        if($modelAsks) {
            foreach($modelAsks as $row) {
                $type = $row['_group'];
                $finalKey = $row['code'];
                $keyExiste = $askArray[$finalKey] ?? false;
                if($keyExiste) {
                    //model déjà instancié on ne le traite pas. 
                    continue;
                } else {
                    $content = \Twig::parse($row['content'], ['ds' => $this->getValues()]);
                    $askArray[$finalKey] = $content;
                }
            }
        }
        return $askArray;
    }

    /**
     * PARTIUE PERMETTATN DE GERER LES SCOPES --------------
     */
    public function getScopesLists() {
        $scopes = $this->config['scopes'];
        $array = [];
        foreach($scopes as $key=>$scope) {
            $array[$key] = $scope['label'];

        }
        //trace_log($array);
        return $array;

    }
     public function getScopeOptions($key) {
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
                /**Clean */
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

    public function getDotedValues($modelId = null, $forceSourceName = false)
    {
        $constructApi = $this->getValues($modelId);
        $api = [];
        if($forceSourceName) {
            $api[$forceSourceName] = $constructApi;
        } else {
             $api[snake_case($this->name)] = $constructApi;
        }
        return array_dot($api);
    }

    /**
     * PARTIE SUR LES WORKFLOW ----------
     */
    public function getWorkflowState()
    {
        if (!$this->model) {
            throw new ApplicationException('model pas instancié pour la fonction getWorkflowState');
        }
        return $this->model->wfPlaceLabel();
    }

    public function getStateLogsValues($modelId = null)
    {
        //trace_log('getStateLogsValues');
        $this->instanciateModel($modelId);
        $results = $this->model->state_logs()->orderBy('created_at')->get()->toArray();
        return $results;
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

    public function getImagesFilesFrom($type, $code = null) {
        $code ? $code : $this->code;
        $staticModel = new $this->class;
        //trace_log("code = ".$code);
        if($code != $this->code) {
            $relation = \DataSources::find($code);
            $staticModel = new $relation->class;
        }
        $files = $staticModel->attachOne; 
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
