<?php namespace Waka\Utils\Models;

use Model;
use Schema;
use \Waka\Cloudis\Models\Settings as CloudisSettings;

/**
 * DataSource Model
 */
class DataSource extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \Waka\Utils\Classes\Traits\StringRelation;
    use \Waka\Cloudis\Classes\Traits\CloudisKey;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_data_sources';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['client_id', 'id'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'name' => 'required',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = ['relations_list', 'attributes_list', 'relations_array_list', 'contacts'];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    public $timestamps = false;

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function getModelClassAttribute()
    {
        return $this->author . '\\' . $this->plugin . '\\models\\' . $this->model;
    }
    public function getHasRelationArrayAttribute()
    {
        if (!$this->relations_array_list) {
            return false;
        }

        if (count($this->relations_array_list)) {
            return true;
        } else {
            return false;
        }
    }
    public function getHasRelationAttribute()
    {
        if (!$this->relations_list) {
            return false;
        }

        if (count($this->relations_list)) {
            return true;
        } else {
            return false;
        }
    }

    public function getTargetModel($id = null)
    {
        $targetModel = $this->modelClass;
        if (!$id) {
            //$id = $targetModel::first()->id;
            $id = $this->test_id;
        }
        return $targetModel::find($id);
    }

    public function getRelationCollection($id = null)
    {
        //trace_log("getRelationCollection");
        $collection = new \October\Rain\Support\Collection();
        if ($this->hasRelationArray) {
            //trace_log("getRelationCollection hasRelationArray");
            foreach ($this->relations_array_list as $relation) {
                $data = [
                    'name' => $relation['name'] ?? null,
                    'param' => $relation['param'] ?? null,
                    'options' => $this->getRelationQuery($relation['name'], $id)->lists('name', 'id'),
                    'key' => $relation['key'] ?? null,
                    'relations_list' => $relation['relations_list'] ?? null,
                ];
                $collection->push($data);
            }
        }
        return $collection;

    }
    public function getRelationFromParam(String $key)
    {
        foreach ($this->relations_array_list as $relation) {
            if ($relation['param'] ?? false == $key) {
                return $relation['name'];
            }
        }
        return null;

    }

    public function getRelationQuery($relation, $id = null)
    {
        $targetModel = $this->modelClass;
        $relationModel = null;
        if (!$id) {
            if ($targetModel::first() ?? false) {
                $relationModel = $targetModel::first()->{$relation}();
            }

        } else {
            if ($targetModel::find($id) ?? false) {
                $relationModel = $targetModel::find($id)->{$relation}();
            }

        }
        return $relationModel;
    }

    public function getModels($id = null)
    {
        $targetModel = $this->modelClass;
        if (!$id) {
            //$id = $targetModel::first()->id;
            $id = $this->test_id;
        }
        $embedRelation = null;
        $constructApi = null;
        if (count($this->relations_list)) {
            $embedRelation = array_pluck($this->relations_list, 'name');
            $constructApi = $targetModel::with($embedRelation)->find($id);
        } else {
            $constructApi = $targetModel::find($id);
        }
        return $constructApi;
    }
    public function getValues($id = null)
    {
        return $this->getModels($id)->toArray();
    }

    public function getDotedValues($id = null)
    {
        $constructApi = $this->getValues($id);
        $api[snake_case($this->model)] = $constructApi;
        return array_dot($api);
    }

    /**
     * Cette fonction utulise le trait CloudisKey
     */
    public function getAllPicturesKey($id = null)
    {
        //Recherche du model
        $targetModel = $this->getTargetModel($id);

        $gi = new \Waka\Cloudis\Classes\GroupedImages($targetModel);
        return $gi->getLists($this);

    }

    public function getOnePictureKey($key, $id = null)
    {
        //Recherche du model
        $targetModel = $this->getTargetModel($id);

        $gi = new \Waka\Cloudis\Classes\GroupedImages($targetModel);
        return $gi->getOne($this, $key);

    }

    public function getPicturesUrl($id, $dataImages)
    {
        if (!$dataImages) {
            return;
        }
        $allPictures = [];
        $targetModel = $this->getTargetModel($id);
        //  trace_log("--dataImages--");
        //  trace_log($dataImages);
        foreach ($dataImages as $image) {
            //On recherche le bon model
            $modelImage = $targetModel;
            $img;

            if ($image['relation'] != 'self') {
                $modelImage = $this->getStringModelRelation($targetModel, $image['relation']);
            }
            trace_log("nom du model " . $modelImage->name);

            $options = [
                'width' => $image['width'] ?? null,
                'height' => $image['height'] ?? null,
                'crop' => $image['crop'] ?? null,
            ];

            // si cloudi ( voir GroupedImage )
            if ($image['type'] == 'cloudi') {
                $img = $modelImage->{$image['field']};
                if ($img) {
                    $img = $img->getUrl($options);
                } else {
                    $img = \Cloudder::secureShow(CloudisSettings::get('srcPath'));
                }
                //  trace_log('image cloudi---' . $img);
            }
            // si montage ( voir GroupedImage )
            if ($image['type'] == 'montage') {
                $montage = $modelImage->montages->find($image['id']);
                $img = $modelImage->getCloudiModelUrl($montage, $options);
                //  trace_log('montage ---' . $img);
            }
            $allPictures[$image['code']] = [
                'url' => $img,
                'width' => $options['width'],
                'height' => $options['height'],
            ];

        }
        return $allPictures;
    }

    /**
     * Je ne sais plus a quoi ca sert
     */
    public function listModelNameId()
    {
        return $this->modelClass::lists('name', 'id');
    }
    public function get()
    {
        return $this->modelClass::lists('name', 'id');
    }
    public function listTableColumns()
    {
        return Schema::getColumnListing($this->modelClass::first()->getTable());

    }
    public function getImagesList($id = null)
    {
        return $this->encryptKeyedImage($this, $id);
    }

    /**
     * Utils for EMAIL ---------------------------------------------------
     */

    /**
     * Return model
     */
    public function getDataFromContacts($type)
    {
        $array = $this->contacts;
        if (!$array) {
            throw new \ApplicationException("Les contacts ne sont pas configurés.");
        }
        $fields = $array[$type] ?? null;

        if (!$fields['key']) {
            ////  trace_log('key est vide');
            return;
        }

        return [
            'key' => $fields['key'] ?? null,
            'relation' => $fields['relation'] ?? null,
        ];
    }
    /**
     * Fonctions d'identifications des contacts, utilises dans les popup de wakamail
     * getstringrelation est dans le trait StringRelation
     */
    public function getContact($type, $id = null)
    {
        //  trace_log("get contact from type : " . $type);
        $targetModel = $this->getTargetModel($id);
        $emailData = $this->getDataFromContacts($type);

        if (!$emailData) {
            return;
        }
        //trace_log($emailData['relation']);
        $contacts = $this->getStringRelation($targetModel, $emailData['relation']);
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
        if (!$this->function_class) {
            throw new \ApplicationException("Il manque le chemin de la classe fonction dans DataSource pour ce model");
        }
        $fn = new $this->function_class;
        return $fn->getFunctionsList();
    }
    public function getFunctionsOutput($fnc)
    {
        if (!$this->function_class) {
            throw new \ApplicationException("Il manque le chemin de la classe fonction dans DataSource pour ce model");
        }
        $fn = new $this->function_class;
        return $fn->getFunctionsOutput($fnc);
    }
    /**
     * retourne simplement le function class. mis en fonction pour ajouter l'application exeption sans nuire à la lisibitilé de la fonction getFunctionsCollections
     */
    public function getFunctionClass()
    {
        if (!$this->function_class) {
            return null;
        }
        return new $this->function_class;
    }
    /**
     * Retourne les valeurs d'une fonction du model de se datasource.
     * templatemodel = wakamail ou document ou aggregator
     * id est l'id du model de datasource
     */

    public function getFunctionsCollections($id, $model_functions)
    {
        if (!$model_functions) {
            return;
        }
        $model = $this->getTargetModel($id);

        $collection = [];
        $fnc = $this->getFunctionClass();
        $fnc->setModel($model);

        foreach ($model_functions as $item) {
            $itemFnc = $item['functionCode'];
            $collection[$item['collectionCode']] = $fnc->{$itemFnc}($item);
        }
        return $collection;
    }

}
