<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * RuleFnc Model
 */
class RuleFnc extends SubFormModel
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_rule_fncs';
    public $staticAttributes = ['fnc_text'];
    public $realFields = ['photo', 'photos', 'code' , 'is_shared'];

    /**
     * @var array Relations
     */
    public $morphTo = [
        'fnceable' => [],
    ];

    public $attachOne = [
        'photo' => [
            'System\Models\File',
            'delete' => true
        ],
    ];
    public $attachMany = [
        'photos' => [
            'System\Models\File',
            'delete' => true
        ],
    ];

    public function afterSave()
    {
        // Make sure that this record is removed from the DB after being removed from a rule
        //"La partie ci dessous est à retravailler. elle ne fonctionnait pas avant semble t il et encore moins avec les modifs pour fncs;
        \Log::error('voir 91 todo  fichier rule fnc');
        $removedFromRule = $this->rule_host_id === null && $this->getOriginal('rule_host_id');
        if ($removedFromRule && !$this->notification_rule()->withDeferred(post('_session_key'))->exists()) {
            $this->delete();
        }
    }
}
