<?php namespace Waka\Utils\Models;

use Model;
use Schema;

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
    protected $jsonable = ['relations_list', 'attributes_list', 'relations_array_list'];

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

    public function beforeSave()
    {

    }

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
    public function getDotedRelationValues($id, array $params)
    {
        $targetModel = $this->modelClass;
        if (!$id) {
            $id = $targetModel::first()->id;
        }
        $embedRelation = null;
        $constructApi = null;
        $api = [];
        trace_log('params');
        trace_log($params);
        if (count($params)) {
            foreach ($this->relations_array_list as $relation) {
                trace_log("Parseur de relation");
                $relationName = $relation['name'];
                $relationParam = $relation['param'];
                $relationValue = $params[$relationParam] ?? false;
                trace_log("relationName : " . $relationName);
                trace_log("relationValue : " . $relationValue);

                if ($relationValue) {
                    $keyRel = snake_case($this->model) . '.' . $relationName;

                    if (count($relation['relations_list'])) {
                        trace_log($relation['relations_list']);
                        trace_log(array_pluck($relation['relations_list'], 'name'));
                        $embedRelation = array_pluck($relation['relations_list'], 'name');
                        $api[$keyRel] = $targetModel::find($id)->{$relationName}()->with($embedRelation)->find($relationValue)->toArray();

                    } else {

                        $api[$keyRel] = $targetModel::find($id)->{$relationName}->find($relationValue)->toArray();
                    }

                } else {
                    //mesage sir pas de relation value
                }
                //fin du foreach
            }
            // fin du if parametre
        } else {
            //si il n' y a pas de parametre
        }
        // trace_log("-----Array dot-----");
        // trace_log(array_dot($api));
        return array_dot($api);
        //return null;
    }
    public function getAllPictures($id = null)
    {
        //trait CloudisKey
        return $this->getDotedImagesList($this, $id);
    }

    public function getValues($id = null)
    {
        $targetModel = $this->modelClass;
        if (!$id) {
            $id = $targetModel::first()->id;
        }
        $embedRelation = null;
        $constructApi = null;
        if (count($this->relations_list)) {
            $embedRelation = array_pluck($this->relations_list, 'name');
            $constructApi = $targetModel::with($embedRelation)->find($id)->toArray();
        } else {
            $constructApi = $targetModel::find($id)->toArray();
        }
        return $constructApi;
    }

    public function getDotedValues($id = null)
    {
        $constructApi = $this->getValues($id);

        $api[snake_case($this->model)] = $constructApi;
        return array_dot($api);
    }
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
     * Return model
     */

    public function getContactFromYaml($type)
    {
        $array = \Yaml::parse($this->contacts);
        $array = $array[$type] ?? null;
        if (!$array) {
            throw new \ApplicationException("Il manque l'info email dans de data_source ask_contacts");
        }

        return [
            'key' => $array['key'],
            'relation' => $array['relation'] ?? null,
        ];
    }

    /**
     * FOnctions d'identifications des contacts
     */
    public function getContact($id = null)
    {
        $targetModel = $this->modelClass;
        if (!$id) {
            $id = $targetModel::first()->id;
        }
        $emailData = $this->getContactFromYaml('ask_to');

        $datas = $this->getStringRelation($targetModel::find($id), $emailData['relation']);
        if ($datas->count()) {
            $datas = $datas->lists($emailData['key']);
        } else {
            $datas[0] = $datas[$emailData['key']];
        }
        return $datas;

    }
    public function getCcContact($type, $id = null)
    {
        $targetModel = $this->modelClass;
        if (!$id) {
            $id = $targetModel::first()->id;
        }
        $ccEmail = $this->getContactFromYaml($type);

        $model = $targetModel::find($id);
        return $this->getStringRelation($targetModel::find($id), $ccEmail['relation'])->lists($ccEmail['key']);

    }

    public function getFunctionsList()
    {
        if (!$this->function_class) {
            throw new \ApplicationException("Il manque le chemin de la classe fonction dans DataSource pour ce model");
        }
        $fn = new $this->function_class;
        return $fn->getFunctionsList();
    }
    public function getFunctionClass()
    {
        if (!$this->function_class) {
            throw new \ApplicationException("Il manque le chemin de la classe fonction dans DataSource pour ce model");
        }
        return new $this->function_class;
    }
    // public function getFunctionsCollections($id)
    // {
    //     $targetModel = $this->modelClass;
    //     if (!$id) {
    //         $id = $targetModel::first()->id;
    //     }
    //     return $targetModel::find($id)->getFunctionsCollection();
    // }
    public function getFunctionsCollections($id, $wakaModel)
    {
        $targetModel = $this->modelClass;
        if (!$id) {
            $id = $targetModel::first()->id;
        }
        $model = $targetModel::find($id);

        $collection = [];
        $fnc = $this->getFunctionClass();
        $fnc->setModel($model);
        foreach ($wakaModel->model_functions as $item) {
            $itemFnc = $item['functionCode'];
            $collection[$item['collectionCode']] = $fnc->{$itemFnc}($item);
        }
        return $collection;
    }

}
