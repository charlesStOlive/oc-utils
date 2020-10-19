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
        if (class_exists('\Waka\Cloudis\Classes\Cloudi')) {
            //trace_log(\Config::get('waka.cloudis::ImageOptions.crop'));
            return \Config::get('waka.cloudis::ImageOptions.crop.options');
        } else {
            return [
                'exact' =>"Exacte",
                'portrait' => "Portrait",
                'landscape' => "Paysage",
                'auto' => "automatique",
                'fit' => 'Tenir',
                'crop' => "Couper",
            ];
        }
    }

    public function listGravity() {
        if (class_exists('\Waka\Cloudis\Classes\Cloudi')) {
            //trace_log(\Config::get('waka.cloudis::ImageOptions.gravity'));
                return \Config::get('waka.cloudis::ImageOptions.gravity.options');
            } else {
                return [];
            }
    }

    public function filterFields($fields, $context = null)
    {
       
        //trace_log("source : " .$this->source);
        
        // if ($this->source == 'http') {
        //     $fields->source_url->hidden = false;
        //     $fields->git_branch->hidden = true;
        // }
        
    }
}
