<?php namespace Waka\Utils\Models;

use Model;

/**
 * JobList Model
 */
class JobList extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_job_lists';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

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
    protected $jsonable = ['payload'];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = ['counterByState'];

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
        'started_at',
        'end_at',
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'user' => ['\Backend\Models\User'],
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /**
     * GETTER
     */

    public function getDateDiffAttribute()
    {
        if (!$this->started_at) {
            return null;
        }
        if (!$this->end_at) {
            return null;
        }
        return $this->started_at->diffInSeconds($this->end_at);
    }

    /**
     * Scopes
     */
    public function scopeOnlyUser($query, $filter = true)
    {
        $user = \BackendAuth::getUser();
        if (!$user || !$filter) {
            return $query;
        }
        return $query->where('user_id', $user->id);

    }
    public function scopeState($query, $state)
    {
        if ($state == 'end') {
            return $query->where('state', 'Terminé');
        }
        if ($state == 'error') {
            return $query->where('state', 'Erreur');
        }
        if ($state == 'run') {
            return $query->whereIn('state', ['En cours', 'Attente']);
        }

    }
}
