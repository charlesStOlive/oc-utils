<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * Rule Model
 */
class RuleBloc extends SubFormModel
{
    
    /**
     * @var string The database table used by the model.
     */
    public $table = 'waka_utils_rule_blocs';

    public $staticAttributes = ['rule_text'];
    public $realFields = ['code' , 'is_share'];
    public $morphTo = [
        'bloceable' => [],
    ];


    public function afterSave()
    {
        // Make sure that this record is removed from the DB after being removed from a rule
        $removedFromRule = $this->rule_bloceable_id === null && $this->getOriginal('rule_bloceable_id');
        if ($removedFromRule && !$this->notification_rule()->withDeferred(post('_session_key'))->exists()) {
            $this->delete();
        }
    }

}
