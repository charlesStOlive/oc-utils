<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\ExtensionBase;
use Waka\Utils\Classes\DataSource;
use Waka\Utils\Interfaces\Rule as RuleInterface;

/**
 * Notification rule base class
 *
 * @package waka\utils
 * @author Alexey Bobkov, Samuel Georges
 */
class RuleConditionBase extends RuleBase implements RuleInterface
{
    private $error;
    
    /**
     * Returns information about this rule, including name and description.
     */
    public function ruleDetails()
    {
        return [
            'name'        => 'Condition',
            'description' => 'Condition description',
            'icon'        => 'icon-dot-circle-o',
        ];
    }

    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []) {

    }

    /**
     * Boot method called when the condition class is first loaded
     * with an existing model.
     * @return array
     */

    public function getModel() {
        return $this->host->conditioneable;
    }

    public function setError($error = null) {
        $errorName = $error ? $error : $this->getText()." non compatible";
        $this->error = $errorName;
    }

    public function getError() {
        return $this->error ? $this->error : 'Erreur condition non spécifié';
    }
    public function listOperators() {
        return [
            'where' => "Est égale à ",
            'whereNot' => "Est différent de",
            'wherein' => "Est dans ces valeurs",
            'whereNotIn' => "N'est pas dans ces valeurs",
            // 'like' => "contient",
            // 'notLke' => "ne contient pas",
        ];
    }


    
}
