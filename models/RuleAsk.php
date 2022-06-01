<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * RuleAsk Model
 */
class RuleAsk extends SubFormModel
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_rule_asks';
    public $staticAttributes = ['ask_text'];
    public $realFields = ['photo', 'photos', 'code', 'is_share', 'askeable_id', 'askeable_type'];

    /**
     * @var array Relations
     */
    public $morphTo = [
        'askeable' => [],
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
        $removedFromRule = $this->rule_askeable_id === null && $this->getOriginal('rule_askeable_id');
        if ($removedFromRule && !$this->notification_rule()->withDeferred(post('_session_key'))->exists()) {
            $this->delete();
        }
    }
}
