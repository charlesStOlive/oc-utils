<?php namespace Waka\Utils\Models;

use Model;

/**
 * UserCreateable Model
 */
class TestWf extends Model
{
    use \Waka\Utils\Classes\Traits\WakaWorkflowTrait;

    public $implement = [
        'October.Rain.Database.Behaviors.Purgeable',
    ];
    public $purgeable = [
    ];
}
