<?php namespace Waka\Utils\Models;

use Model;
use Schema;
use October\Rain\Support\Collection;

/**
 * DataSource Model
 */
class DataSource extends Model
{
    use \October\Rain\Database\Traits\Validation;
        
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
        'name' => 'required'
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = ['relations_list', 'attributes_list'];

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

    public function getModelClassAttribute() {
        return $this->author.'\\'.$this->plugin.'\\models\\'.$this->model;
    }

    public function listApi($id=null) {
        $targetModel = $this->modelClass;
        if(!$id) $id = $targetModel::first()->id;
        // $targetModel = $this->modelClass::first();
        // $columns = new Collection();
        // $columns->push(Schema::getColumnListing($targetModel->table));
        // if(count($targetModel->notPublishable)) {
        //     $columns = $columns->reject(function ($item) use($targetModel) {
        //         return in_array($item, $targetModel->notPublishable);
        //     });
        // }
        //trace_log($targetModel->relations_list);
        //$columns->put($targetModel->relations_list);
        //trace_log($columns);
        $embedRelation = null;
        $constructApi = null;
        if(count($this->relations_list)) {
            $embedRelation = array_pluck($this->relations_list, 'name');
            $constructApi = $targetModel::with($embedRelation)->find($id)->toArray();
        } else {
            $constructApi = $targetModel::find($id)->toArray();
        }
        $api[snake_case($this->model)] = $constructApi;
        return array_dot($api);
    }
}
