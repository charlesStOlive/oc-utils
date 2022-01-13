<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * Rule Model
 */
class RuleCondition extends SubFormModel
{
    
    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_rules_conditions';

    public $staticAttributes = ['rule_text'];
    public $realFields = ['code' , 'is_shared'];

    public $morphTo = [
        'conditioneable' => [],
    ];

    

    public function afterSave()
    {
        // Make sure that this record is removed from the DB after being removed from a rule
        $removedFromRule = $this->rule_conditioneable_id === null && $this->getOriginal('rule_conditioneable_id');
        if ($removedFromRule && !$this->notification_rule()->withDeferred(post('_session_key'))->exists()) {
            $this->delete();
        }
    }

}
