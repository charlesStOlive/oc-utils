<?php namespace Waka\Utils\Models;

use Model;

/**
 * DatasourceLog Model
 */
class SourceLog extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_source_logs';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [''];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['*'];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = ['datas'];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = ['created_at', 'updated_at', 'sourceable_id', 'sourceable_type', 'target_id', 'target_type'];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'log_state' => ['Waka\Utils\Model\LogState'],
    ];
    public $belongsToMany = [];
    public $morphTo = [
        'sendeable' => [],
        'send_targeteable' => [],
    ];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];
}
