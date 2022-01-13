<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * Rule Model
 */
class RuleContent extends SubFormModel
{
    
    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_rules_contents';

    public $staticAttributes = ['rule_text'];
    public $realFields = ['photo', 'photos', 'code' , 'is_shared'];
    public $morphTo = [
        'contenteable' => [],
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
        $removedFromRule = $this->rule_contenteable_id === null && $this->getOriginal('rule_contenteable_id');
        if ($removedFromRule && !$this->notification_rule()->withDeferred(post('_session_key'))->exists()) {
            $this->delete();
        }
    }

}
