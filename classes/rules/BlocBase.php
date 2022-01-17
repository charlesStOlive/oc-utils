<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\ExtensionBase;
use Waka\Utils\Classes\DataSource;
use View;

/**
 * Notification rule base class
 *
 * @package waka\utils
 * @author Alexey Bobkov, Samuel Georges
 */
class BlocBase extends SubForm
{
    protected $morphName;

    public function __construct($host = null)
    {
        $this->morphName = 'bloceable';
        $this->init('/waka/utils/models/rules/fields_bloc.yaml');
        if (!$this->host = $host) {
            return;
        }
        $this->boot($host);
        /*
         * Paths
         */
        
    }
}
