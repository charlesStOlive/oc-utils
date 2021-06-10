<?php namespace Waka\Utils\Models;

use Backend\Models\User as BackendUser;
use Backend\Models\UserRole;
use Model;

/**
 * ScopeFunctions Model
 */
class ScopeFunction extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

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
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function listUsers()
    {
        $backendUser = BackendUser::get(['first_name', 'last_name', 'id']);
        $backendUser = $backendUser->keyBy('id');
        $backendUser->transform(function ($item, $key) {
            return $item['first_name'] . ' ' . $item['last_name'];
        });
        //trace_log($backendUser);
        return $backendUser->toArray();
    }
    public function listUserRoles()
    {
        return UserRole::lists('name', 'id');
    }
}
