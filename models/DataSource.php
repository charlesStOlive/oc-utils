<?php namespace Waka\Utils\Models;

use Model;
use Schema;
use October\Rain\Support\Collection;
use Yaml;
use ApplicationException;

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

    public function afterSave() {
        trace_log($this->getImagesList());
    }

    public function getModelClassAttribute() {
        return $this->author.'\\'.$this->plugin.'\\models\\'.$this->model;
    }

    public function listApi($id=null) {
        $targetModel = $this->modelClass;
        if(!$id) $id = $targetModel::first()->id;
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
    public function listModelNameId() {
        return $this->modelClass::lists('name', 'id' );
    }
    public function get() {
        return $this->modelClass::lists('name', 'id' );
    }
    public function listTableColumns() {
        return Schema::getColumnListing($this->modelClass::first()->getTable());
        
    }
    public function getImagesList($id=null) {
        $targetModel;
        if(!$id) {
            $targetModel = $this->modelClass::first();
        } else {
            $targetModel = $this->modelClass::find($id);
        }
        //trace_log('name : '.$targetModel->name);
        $collection = new \October\Rain\Support\Collection();
        $datas = Yaml::parse($this->media_files);
        //trace_log($datas);
        //trace_log(get_class($targetModel));
        foreach($datas as $key => $data) {
            $tempModel = $targetModel;
            if(array_key_exists('from', $data)) {
                if(!$targetModel[$data['from']]) throw new ApplicationException('dataSource model relation not exist : '.$data['from']);
                // nous sommes dans une relation. 
                $tempModel = $targetModel[$data['from']];
            }
            if(!$data['type']) throw new ApplicationException('dataSource type missing');
            //
            switch ($data['type']) {
                case 'file':
                    $collection->push([
                        'name' => $data['label'],
                        'url' => $tempModel[$key]->getLocalPath()
                    ]);
                    break;
                case 'media':
                    $collection->push([
                        'name' => $data['label'],
                        'url' => storage_path('app/media/'.$tempModel[$key])
                    ]);
                    break;
                case 'montages':
                    trace_log('montages : '. get_class($tempModel));
                    foreach($tempModel->montages as $montage) {
                        $collection->push([
                            'name' => $data['label'].' : '.$montage->name,
                            'url' => $montage->getCloudiUrl('src')
                        ]);

                    }
                    break;
            }


        }
        return $collection;
    }
}
