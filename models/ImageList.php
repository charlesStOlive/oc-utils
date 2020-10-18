<?php namespace Waka\Utils\Models;

use Model;

/**
 * ImageList Model
 */
class ImageList extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'code' => 'required',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = [];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

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

    public function listCrop() {
        //trace_log($this->source);
        if($this->source == 'cloudi' || $this->source == 'montage') {


        }
        return [
            'exact' =>"Exacte",
            'portrait' => "Portrait",
            'landscape' => "Paysage",
            'auto' => "automatique",
            'fit' => 'Tenir',
            'crop' => "Couper",
        ];
    }

    public function listGravity() {
        return [];
        
    }
}
