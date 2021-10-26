<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * Rule Model
 */
class RuleFilter extends Rule
{
    
    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_rules_filters';

    public $morphTo = [
        'filtereable' => [],
    ];

    public function afterSave()
    {
        // Make sure that this record is removed from the DB after being removed from a rule
        $removedFromRule = $this->rule_filtereable_id === null && $this->getOriginal('rule_filtereable_id');
        if ($removedFromRule && !$this->notification_rule()->withDeferred(post('_session_key'))->exists()) {
            $this->delete();
        }
    }
}
