<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\ExtensionBase;
use Waka\Utils\Classes\DataSource;


/**
 * Notification ask base class
 *
 * @package waka\utils
 * @author Alexey Bobkov, Samuel Georges
 */
class AskBase extends SubForm
{
    protected $morphName;                              

    /**
     * Constructeur 
     */

    public function __construct($host = null)
    {
        $this->morphName = 'askeable';
        $this->init('/waka/utils/models/rules/fields_ask.yaml');
        if (!$this->host = $host) {
            return;
        }
        $this->boot($host);
    }

    /**
     * Fonction unisque sur ASK
     */

    public function getWordType()
    {
        return array_get($this->subFormDetails(), 'outputs.word_type');
    }

    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {
        return 'resolve is missing in '.$this->getSubFormName();
    }

}
