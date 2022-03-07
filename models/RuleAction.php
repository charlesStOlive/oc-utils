<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * Rule Model
 */
class RuleAction extends SubFormModel
{
    
    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_rules_actions';

    public $staticAttributes = ['rule_text'];
    public $realFields = ['code' , 'is_share'];

    public $morphTo = [
        'actioneable' => [],
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
        $removedFromRule = $this->rule_actioneable_id === null && $this->getOriginal('rule_actioneable_id');
        if ($removedFromRule && !$this->notification_rule()->withDeferred(post('_session_key'))->exists()) {
            $this->delete();
        }
    }

}
